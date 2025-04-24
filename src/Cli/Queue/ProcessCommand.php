<?php
declare(strict_types=1);

namespace LesAbstractService\Cli\Queue;

use Override;
use LesQueue\Job\Job;
use LesQueue\Job\Property\Name;
use LesQueue\Queue;
use LesQueue\Worker\Worker;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ProcessCommand extends Command
{
    /** @var array<string, Worker|string> */
    private array $workerMap;

    /**
     * @param array<string, Worker|string> $workerMap
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
        private readonly Queue $queue,
        array $workerMap,
    ) {
        parent::__construct();

        $this->workerMap = $workerMap;
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queue->process(
            function (Job $job) use ($output) {
                if ($job->name->value === 'queue:quit') {
                    $output->writeln('Queue quit');
                    $this->queue->stopProcessing();

                    $this->queue->delete($job);
                } else {
                    if ($output->isVerbose()) {
                        $output->writeln("process: {$job->name}");
                    }

                    try {
                        $this
                            ->getWorkerForJob($job->name)
                            ->process($job);

                        $this->queue->delete($job);
                    } catch (Throwable $e) {
                        $this->queue->bury($job);

                        if ($output->isVerbose()) {
                            throw $e;
                        }

                        $this->logger->critical(
                            'Failed processing job',
                            ['exception' => $e],
                        );
                    }
                }
            }
        );

        return Command::SUCCESS;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getWorkerForJob(Name $name): Worker
    {
        if (!array_key_exists($name->value, $this->workerMap)) {
            throw new RuntimeException();
        }

        $mapped = $this->workerMap[$name->value];

        if (is_string($mapped)) {
            $worker = $this->container->get($mapped);
            assert($worker instanceof Worker);

            return $this->workerMap[$name->value] = $worker;
        }

        return $mapped;
    }
}
