<?php

declare(strict_types=1);

namespace LesAbstractService\Cli\Queue;

use Override;
use LesQueue\Queue;
use LesValueObject\Composite\Paginate;
use LesValueObject\Number\Int\Paginate\Page;
use Symfony\Component\Console\Command\Command;
use LesValueObject\Number\Int\Paginate\PerPage;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ReanimateCommand extends Command
{
    public function __construct(private readonly Queue $queue)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption('page', mode: InputOption::VALUE_OPTIONAL, default: 1);
        $this->addOption('perPage', mode: InputOption::VALUE_OPTIONAL, default: 25);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = $input->getArgument('page');
        assert(is_int($page) || is_string($page));

        $perPage = $input->getArgument('perPage');
        assert(is_int($perPage) || is_string($perPage));

        $paginate = new Paginate(
            new PerPage((int)$perPage),
            new Page((int)$page),
        );

        foreach ($this->queue->getBuried($paginate) as $job) {
            $this->queue->reanimate($job->id);
        }

        return self::SUCCESS;
    }
}
