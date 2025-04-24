<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Cli;

use Override;
use LesDomain\Event\Store\Store;
use LesDomain\Event\Property\Headers;
use Symfony\Component\Console\Command\Command;
use LesValueObject\Composite\ForeignReference;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use LesValueObject\Number\Int\Date\MilliTimestamp;
use Symfony\Component\Console\Output\OutputInterface;
use LesAbstractService\Permission\Event\UpdatedEvent;
use LesAbstractService\Permission\Model\Attributes\Flags;
use LesAbstractService\Permission\Repository\PermissionsRepository;

final class UpdateCommand extends Command
{
    public function __construct(
        private readonly PermissionsRepository $permissionsRepository,
        private readonly Store $store,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument('identity', InputArgument::REQUIRED)
            ->addOption('grant')
            ->addOption('read')
            ->addOption('create')
            ->addOption('update')
            ->addOption('all');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $identity = $this->getIdentity($input);

        if (!$this->permissionsRepository->existsWithIdentity($identity)) {
            $output->writeln('Identity has no permissions registered');

            return self::FAILURE;
        }

        $permissions = $this->permissionsRepository->getWithIdentity($identity);
        $this
            ->store
            ->persist(
                new UpdatedEvent(
                    $permissions->id,
                    new Flags(
                        $input->getOption('all') || $input->getOption('grant'),
                        $input->getOption('all') || $input->getOption('read'),
                        $input->getOption('all') || $input->getOption('create'),
                        $input->getOption('all') || $input->getOption('update'),
                    ),
                    MilliTimestamp::now(),
                    Headers::forCli(),
                ),
            );

        return self::SUCCESS;
    }

    private function getIdentity(InputInterface $input): ForeignReference
    {
        $identity = $input->getArgument('identity');
        assert(is_string($identity));

        return ForeignReference::fromString($identity);
    }
}
