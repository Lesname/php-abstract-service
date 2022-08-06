<?php
declare(strict_types=1);

namespace LessAbstractService\Cli\Queue;

use LessQueue\Job\Property\Name;
use LessQueue\Queue;
use LessQueue\Worker\Worker;
use LessValueObject\Number\Exception\MaxOutBounds;
use LessValueObject\Number\Exception\MinOutBounds;
use LessValueObject\Number\Exception\PrecisionOutBounds;
use LessValueObject\Number\Int\Time\Second;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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

    public function configure(): void
    {
        $this->addArgument('timeout', InputArgument::OPTIONAL);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws PrecisionOutBounds
     * @throws Throwable
     * @throws ContainerExceptionInterface
     * @throws MaxOutBounds
     * @throws MinOutBounds
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeout = $input->getArgument('timeout');
        $timeout = is_string($timeout) && ctype_digit($timeout)
            ? (int)$timeout
            : 10;
        $maxTimeout = 60 * 15;

        if ($timeout < 0) {
            throw new RuntimeException('Min 0 for timeout');
        }

        if ($timeout > $maxTimeout) {
            throw new RuntimeException("Max timeout is {$maxTimeout}");
        }

        $till = time() + $timeout;
        $first = true;

        while ($first || $till >= time()) {
            $job = $this->queue->reserve(new Second(min($till - time(), 30)));
            $first = false;

            if ($job) {
                if ($job->getName()->getValue() === 'queue:stop') {
                    $output->writeln('Queue stopped');
                    $this->queue->delete($job);

                    break;
                }

                try {
                    $this->getWorkerForJob($job->getName())->process($job);
                } catch (Throwable $e) {
                    $this->queue->bury($job);

                    if ($output->isVerbose()) {
                        throw $e;
                    }

                    $this->logger->critical(
                        'Failed processing job',
                        ['exception' => $e],
                    );

                    continue;
                }

                $this->queue->delete($job);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getWorkerForJob(Name $name): Worker
    {
        if (!array_key_exists($name->getValue(), $this->workerMap)) {
            throw new RuntimeException();
        }

        $mapped = $this->workerMap[$name->getValue()];

        if (is_string($mapped)) {
            $worker = $this->container->get($mapped);
            assert($worker instanceof Worker);

            return $this->workerMap[$name->getValue()] = $worker;
        }

        return $mapped;
    }
}
