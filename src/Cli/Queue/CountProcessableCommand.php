<?php
declare(strict_types=1);

namespace LessAbstractService\Cli\Queue;

use LessQueue\Queue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CountProcessableCommand extends Command
{
    public function __construct(private readonly Queue $queue)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln((string)$this->queue->countProcessable());

        return self::SUCCESS;
    }
}
