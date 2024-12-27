<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository;

use LessResource\Set\ResourceSet;
use LessValueObject\Composite\Paginate;
use LessResource\Repository\ResourceRepository;
use LessValueObject\Composite\ForeignReference;
use LessDocumentor\Route\Attribute\DocResource;
use LessAbstractService\Permission\Model\Permission;
use LessAbstractService\Permission\Repository\Parameter\Flags;
use LessAbstractService\Permission\Repository\Exception\NoPermission;

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
