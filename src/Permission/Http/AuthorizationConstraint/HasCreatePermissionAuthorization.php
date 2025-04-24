<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Http\AuthorizationConstraint;

use Override;
use LesAbstractService\Permission\Model\Permission;

final class HasCreatePermissionAuthorization extends AbstractPermissionAuthorization
{
    #[Override]
    protected function hasPermissionFlag(Permission $permission): bool
    {
        return $permission->attributes->flags->create;
    }
}
