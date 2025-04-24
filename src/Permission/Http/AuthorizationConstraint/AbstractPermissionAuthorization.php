<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Http\AuthorizationConstraint;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use LesValueObject\Composite\ForeignReference;
use LesAbstractService\Permission\Model\Permission;
use LesAbstractService\Permission\Repository\PermissionsRepository;
use LesAbstractService\Permission\Repository\Exception\NoPermission;
use LesHttp\Middleware\Authorization\Constraint\AbstractIdentityAuthorizationConstraint;

abstract class AbstractPermissionAuthorization extends AbstractIdentityAuthorizationConstraint
{
    public function __construct(private readonly PermissionsRepository $permissionsRepository)
    {}

    #[Override]
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
