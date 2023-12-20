<?php
declare(strict_types=1);

namespace LessAbstractService\Queue\Worker\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use LessIdentity\Account\Model\Account;
use LessIdentity\Account\Service\AccountService;
use LessQueue\Job\Job;
use LessQueue\Queue;
use LessQueue\Worker\Worker;
use LessResource\Set\ResourceSet;
use LessValueObject\Composite\Paginate;
use LessValueObject\Enum\OrderDirection;
use LessValueObject\Number\Exception\MaxOutBounds;
use LessValueObject\Number\Exception\MinOutBounds;
use LessValueObject\Number\Int\Paginate\Page;
use LessValueObject\Number\Int\Paginate\PerPage;
use RuntimeException;
use LessValueObject\Number\Exception\NotMultipleOf;
use LessResource\Set\CollectionValueObjectResourceSet;
use LessIdentity\Account\Service\Parameters\SortableOptions;

final class LoadAccountRolesWorker implements Worker
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly AccountService $accountService,
        private readonly Connection $connection,
        private readonly Queue $queue,
    ) {}

    /**
     * @throws Exception
     * @throws MaxOutBounds
     * @throws MinOutBounds
     * @throws NotMultipleOf
     */
    public function process(Job $job): void
    {
        $accounts = $this->request($job);

        $this->insertAccountRoles($accounts);
        $this->queueNextPage($job, $accounts);
    }

    /**
     * @return ResourceSet<Account>
     *
     * @throws NotMultipleOf
     * @throws MaxOutBounds
     * @throws MinOutBounds
     */
    private function request(Job $job): ResourceSet
    {
        $page = $this->getPage($job);

        $response = $this
            ->accountService
            ->getByRegistered(
                new Paginate(
                    new PerPage(self::PER_PAGE),
                    new Page($page),
                ),
                new SortableOptions(
                    direction: OrderDirection::Ascending,
                ),
            );

        return new CollectionValueObjectResourceSet($response->results, (int)$response->meta->total);
    }

    /**
     * @param ResourceSet<Account> $accounts
     *
     * @throws Exception
     */
    private function insertAccountRoles(ResourceSet $accounts): void
    {
        $insertQuery = <<<'SQL'
INSERT INTO account_role (account_type, account_id, role)
VALUES (:account_type, :account_id, :role)
ON DUPLICATE KEY UPDATE role = :role
SQL;

        $statement = $this->connection->prepare($insertQuery);

        foreach ($accounts as $account) {
            $statement->executeStatement(
                [
                    'account_type' => $account->type,
                    'account_id' => $account->id,
                    'role' => $account->attributes->role->getValue(),
                ],
            );
        }
    }

    /**
     * @param ResourceSet<Account> $accounts
     */
    private function queueNextPage(Job $job, ResourceSet $accounts): void
    {
        $pages = (int)ceil($accounts->count() / self::PER_PAGE);
        $page = $this->getPage($job);

        if ($pages > $page) {
            $this->queue->publish($job->name, ['page' => $page + 1]);
        }
    }

    private function getPage(Job $job): int
    {
        $data = $job->data;

        if (!isset($data['page'])) {
            return 1;
        }

        if (is_int($data['page'])) {
            return $data['page'];
        }

        throw new RuntimeException();
    }
}
