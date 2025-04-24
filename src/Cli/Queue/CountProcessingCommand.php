<?php
declare(strict_types=1);

namespace LesAbstractService\Cli\Queue;

use Override;
use LesQueue\Queue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CountProcessingCommand extends Command
{
    public function __construct(private readonly Queue $queue)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln((string)$this->queue->countProcessing());

        return self::SUCCESS;
    }
}
