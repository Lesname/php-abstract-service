<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository;

use JsonException;
use LessHydrator\Hydrator;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
use LessResource\Set\ResourceSet;
use Doctrine\DBAL\Query\QueryBuilder;
use LessValueObject\Composite\Paginate;
use LessValueObject\Composite\ForeignReference;
use LessResource\Repository\Exception\NoResource;
use LessAbstractService\Permission\Model\Permission;
use LessDatabase\Query\Builder\Applier\PaginateApplier;
use LessResource\Repository\Dbal\Applier\ResourceApplier;
use LessResource\Repository\AbstractDbalResourceRepository;
use LessAbstractService\Permission\Repository\Parameter\Flags;
use LessAbstractService\Permission\Repository\Dbal\PermissionApplier;
use LessAbstractService\Permission\Repository\Exception\NoPermissionWithId;
use LessAbstractService\Permission\Repository\Exception\NoPermissionWithIdentity;

/**
 * @extends AbstractDbalResourceRepository<Permission>
 */
final class DbalPermissionsRepository extends AbstractDbalResourceRepository implements PermissionsRepository
{
    public function __construct(
        private readonly string $serviceName,
        Connection $connection,
        Hydrator $hydrator
    ) {
        parent::__construct($connection, $hydrator);
    }

    /**
     * @throws NoPermissionWithIdentity
     * @throws Exception
     * @throws JsonException
     */
    public function getWithIdentity(ForeignReference $identity): Permission
    {
        $builder = $this->createResourceBuilder();
        $this->applyWithIdentity($builder, $identity);

        try {
            return $this->getResourceFromBuilder($builder);
        } catch (NoResource) {
            throw new NoPermissionWithIdentity($identity);
        }
    }

    /**
     * @throws Exception
     */
    public function existsWithIdentity(ForeignReference $identity): bool
    {
        $builder = $this->createBaseBuilder();
        $this->applyWithIdentity($builder, $identity);

        return $this->getCountFromResultsBuilder($builder) > 0;
    }

    private function applyWithIdentity(QueryBuilder $builder, ForeignReference $identity): void
    {
        $builder->andWhere('p.identity_type = :identity_type');
        $builder->setParameter('identity_type', $identity->type);

        $builder->andWhere('p.identity_id = :identity_id');
        $builder->setParameter('identity_id', $identity->id);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function getWithFlags(Flags $flags, Paginate $paginate): ResourceSet
    {
        $builder = $this->createResourceBuilder();
        (new PaginateApplier($paginate))->apply($builder);

        foreach (['grant', 'read', 'create', 'update'] as $key) {
            if ($flags->{$key} !== null) {
                $builder->andWhere("p.flags_{$key} = :flags_{$key}");
                $builder->setParameter("flags_{$key}", $flags->{$key} ? 1 : 0);
            }
        }

        return $this->getResourceSetFromBuilder($builder);
    }

    protected function getResourceApplier(): ResourceApplier
    {
        return new PermissionApplier($this->serviceName);
    }

    protected function getResourceModelClass(): string
    {
        return Permission::class;
    }

    protected function getNoResourceWithIdClass(): string
    {
        return NoPermissionWithId::class;
    }
}
