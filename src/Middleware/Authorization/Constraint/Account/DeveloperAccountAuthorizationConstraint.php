<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Account;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use LessValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class DeveloperAccountAuthorizationConstraint extends AbstractAccountAuthorizationConstraint
{
    public function __construct(private readonly Connection $connection)
    {}

    /**
     * @throws Exception
     *
     * @psalm-suppress MixedAssignment
     */
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        $builder = $this->connection->createQueryBuilder();
        $role = $builder
            ->select('role')
            ->from('account_role')
            ->andWhere('account_type = :account_type')
            ->setParameter('account_type', $identity->type)
            ->andWhere('account_id = :account_id')
            ->setParameter('account_id', $identity->id)
            ->fetchOne();

        return $role === 'developer';
    }
}
