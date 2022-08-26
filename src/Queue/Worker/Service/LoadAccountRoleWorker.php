<?php
declare(strict_types=1);

namespace LessAbstractService\Queue\Worker\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use LessIdentity\Account\Model\Account;
use LessIdentity\Account\Repository\AccountRepository;
use LessIdentity\Account\Repository\Exception\NoAccount;
use LessQueue\Job\Job;
use LessQueue\Worker\Worker;
use LessValueObject\String\Exception\TooLong;
use LessValueObject\String\Exception\TooShort;
use LessValueObject\String\Format\Exception\NotFormat;
use LessValueObject\String\Format\Resource\Identifier;
use RuntimeException;

final class LoadAccountRoleWorker implements Worker
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly Connection $connection,
    ) {}

    /**
     * @throws Exception
     * @throws NoAccount
     * @throws NotFormat
     * @throws TooLong
     * @throws TooShort
     */
    public function process(Job $job): void
    {
        $account = $this->request($job);

        $this->insertAccountRole($account);
    }

    /**
     * @throws NoAccount
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     */
    private function request(Job $job): Account
    {
        $data = $job->data;

        if (isset($data['reference']) && is_string($data['reference'])) {
            $id = $data['reference'];
        } elseif (isset($data['id']) && is_string($data['id'])) {
            $id = $data['id'];
        } else {
            throw new RuntimeException();
        }

        return $this->accountRepository->getWithId(new Identifier($id));
    }

    /**
     * @throws Exception
     */
    private function insertAccountRole(Account $account): void
    {
        $insertQuery = <<<'SQL'
INSERT INTO account_role (account_type, account_id, role)
VALUES (:account_type, :account_id, :role)
ON DUPLICATE KEY UPDATE role = :role
SQL;

        $statement = $this->connection->prepare($insertQuery);
        $statement->executeStatement(
            [
                'account_type' => $account->type,
                'account_id' => $account->id,
                'role' => $account->attributes->role->getValue(),
            ],
        );
    }
}
