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
 */
#[DocResource(Permission::class)]
interface PermissionsRepository extends ResourceRepository
{
    /**
     * @throws NoPermission
     */
    public function getWithIdentity(ForeignReference $identity): Permission;

    public function existsWithIdentity(ForeignReference $identity): bool;

    /**
     * @return ResourceSet<Permission>
     */
    public function getWithFlags(Flags $flags, Paginate $paginate): ResourceSet;
}
