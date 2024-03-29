<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Cli;

use LessDomain\Event\Store\Store;
use LessDomain\Event\Property\Headers;
use Symfony\Component\Console\Command\Command;
use LessValueObject\Composite\ForeignReference;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use LessValueObject\Number\Int\Date\MilliTimestamp;
use Symfony\Component\Console\Output\OutputInterface;
use LessAbstractService\Permission\Event\UpdatedEvent;
use LessAbstractService\Permission\Model\Attributes\Flags;
use LessAbstractService\Permission\Repository\PermissionsRepository;

final class UpdateCommand extends Command
{
    public function __construct(
        private readonly PermissionsRepository $permissionsRepository,
        private readonly Store $store,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('identity', InputArgument::REQUIRED)
            ->addOption('grant')
            ->addOption('read')
            ->addOption('write');
    }

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
                        (bool)$input->getOption('grant'),
                        (bool)$input->getOption('read'),
                        (bool)$input->getOption('write'),
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
