<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Cli;

use Override;
use LesDomain\Event\Store\Store;
use LesDomain\Event\Property\Headers;
use Symfony\Component\Console\Command\Command;
use LesValueObject\Composite\ForeignReference;
use LesValueObject\Number\Exception\MinOutBounds;
use LesValueObject\Number\Exception\MaxOutBounds;
use Symfony\Component\Console\Input\InputArgument;
use LesValueObject\Number\Exception\NotMultipleOf;
use Symfony\Component\Console\Input\InputInterface;
use LesValueObject\Number\Int\Date\MilliTimestamp;
use Symfony\Component\Console\Output\OutputInterface;
use LesAbstractService\Permission\Event\UpdatedEvent;
use LesAbstractService\Permission\Model\Attributes\Flags;
use LesValueObject\String\Format\Exception\UnknownVersion;
use LesAbstractService\Permission\Repository\PermissionsRepository;
use LesAbstractService\Permission\Repository\Exception\NoPermission;

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

    /**
     * @throws NoPermission
     * @throws MaxOutBounds
     * @throws MinOutBounds
     * @throws NotMultipleOf
     * @throws UnknownVersion
     *
     * @psalm-suppress DeprecatedMethod
     */
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
                    // @phpstan-ignore-next-line
                    MilliTimestamp::now(),
                    Headers::forCli('permission.update'),
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
