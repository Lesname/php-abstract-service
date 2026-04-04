<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Repository;

use LesResource\Set\ResourceSet;
use LesValueObject\Composite\Paginate;
use LesResource\Repository\ResourceRepository;
use LesValueObject\Composite\ForeignReference;
use LesDocumentor\Route\Attribute\DocResource;
use LesAbstractService\Permission\Model\Permission;
use LesAbstractService\Permission\Repository\Parameter\Flags;
use LesAbstractService\Permission\Repository\Exception\NoPermission;

/**
 * @extends ResourceRepository<Permission>
 *
 * @psalm-mutable
 */
#[DocResource(Permission::class)]
interface PermissionsRepository extends ResourceRepository
{
    /**
     * @throws NoPermission
     *
     * @psalm-impure
     */
    public function getWithIdentity(ForeignReference $identity): Permission;

    /**
     * @psalm-impure
     */
    public function existsWithIdentity(ForeignReference $identity): bool;

    /**
     * @psalm-impure
     *
     * @return ResourceSet<Permission>
     */
    public function getWithFlags(Flags $flags, Paginate $paginate): ResourceSet;
}
