<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Http\AuthorizationConstraint;

use Psr\Http\Message\ServerRequestInterface;
use LessValueObject\Composite\ForeignReference;
use LessAbstractService\Permission\Model\Permission;
use LessAbstractService\Permission\Repository\PermissionsRepository;
use LessAbstractService\Permission\Repository\Exception\NoPermission;
use LessHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LessHttp\Middleware\Authorization\Constraint\AbstractIdentityAuthorizationConstraint;

abstract class AbstractPermissionAuthorization extends AbstractIdentityAuthorizationConstraint
{
    public function __construct(private readonly PermissionsRepository $permissionsRepository)
    {}

    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        try {
            $permission = $this->permissionsRepository->getWithIdentity($identity);
        } catch (NoPermission) {
            return false;
        }

        return $this->hasPermissionFlag($permission);
    }

    abstract protected function hasPermissionFlag(Permission $permission): bool;
}
