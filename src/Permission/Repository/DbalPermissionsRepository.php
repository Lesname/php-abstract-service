<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Repository;

use Override;
use JsonException;
use LesHydrator\Hydrator;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
use LesResource\Set\ResourceSet;
use Doctrine\DBAL\Query\QueryBuilder;
use LesValueObject\Composite\Paginate;
use LesValueObject\Composite\ForeignReference;
use LesResource\Repository\Exception\NoResource;
use LesAbstractService\Permission\Model\Permission;
use LesDatabase\Query\Builder\Applier\PaginateApplier;
use LesResource\Repository\Dbal\Applier\ResourceApplier;
use LesResource\Repository\AbstractDbalResourceRepository;
use LesAbstractService\Permission\Repository\Parameter\Flags;
use LesAbstractService\Permission\Repository\Dbal\PermissionApplier;
use LesAbstractService\Permission\Repository\Exception\NoPermissionWithId;
use LesAbstractService\Permission\Repository\Exception\NoPermissionWithIdentity;

/**
 * @extends AbstractDbalResourceRepository<Permission>
 */
final class DbalPermissionsRepository extends AbstractDbalResourceRepository implements PermissionsRepository
{
    /**
     * @psalm-mutation-free
     */
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
     *
     * @psalm-mutable
     */
    #[Override]
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
     *
     * @psalm-mutable
     */
    #[Override]
    public function existsWithIdentity(ForeignReference $identity): bool
    {
        $builder = $this->createBaseBuilder();
        $this->applyWithIdentity($builder, $identity);

        return $this->getCountFromResultsBuilder($builder) > 0;
    }

    /**
     * @psalm-mutable
     */
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
     *
     * @psalm-mutable
     */
    #[Override]
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

    /**
     * @psalm-mutation-free
     */
    #[Override]
    protected function getResourceApplier(): ResourceApplier
    {
        return new PermissionApplier($this->serviceName);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getResourceModelClass(): string
    {
        return Permission::class;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getNoResourceWithIdClass(): string
    {
        return NoPermissionWithId::class;
    }
}
