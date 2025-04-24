<?php
declare(strict_types=1);

namespace LesAbstractService\Cli\Service;

use Override;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CleanUpCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cleanUpEventStore();
        $this->cleanUpThrottleRequest();

        return self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function cleanUpEventStore(): void
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->update('event_store')
            ->set('headers', "json_set(headers, '$.ip', null)")
            ->andWhere('v_header_ip IS NOT NULL')
            ->andWhere('occurred_on <= UNIX_TIMESTAMP(DATE_SUB(now(), INTERVAL 1 year)) * 1000')
            ->executeStatement();
    }

    /**
     * @throws Exception
     */
    private function cleanUpThrottleRequest(): void
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->delete('throttle_request')
            ->andWhere('requested_on <= UNIX_TIMESTAMP(DATE_SUB(now(), INTERVAL 1 month)) * 1000')
            ->executeStatement();
    }
}
