<?php
declare(strict_types=1);

namespace LesAbstractService\Cli\Queue;

use Override;
use LesQueue\Job\Property\Name;
use LesQueue\Parameter\Priority;
use LesQueue\Queue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use LesValueObject\Composite\DynamicCompositeValueObject;

final class QuitCommand extends Command
{
    public function __construct(private readonly Queue $queue)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->queue->countProcessing();

        if ($count > 0) {
            $output->writeln("{$count} active processing");

            for ($i = 1; $i <= $count; $i++) {
                $this->queue->publish(
                    new Name('queue:quit'),
                    new DynamicCompositeValueObject([]),
                    priority: new Priority(5),
                );
            }

            $sleep = 1;

            do {
                sleep(min(10, max($sleep, 1)));
                $count = $this->queue->countProcessing();

                if ($count > 0) {
                    $output->writeln("Still {$count} active processing");
                    $sleep = max(3, $count);
                } else {
                    $output->writeln('Quit all processing');
                }
            } while ($count > 0);
        } else {
            $output->writeln('No queue\'s to quit');
        }

        return self::SUCCESS;
    }
}
