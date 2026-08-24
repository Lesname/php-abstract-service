<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Http\AuthorizationConstraint;

use Override;
use LesAbstractService\Permission\Model\Permission;

/**
 * @deprecated no replacement
 */
final class HasUpdatePermissionAuthorization extends AbstractPermissionAuthorization
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected function hasPermissionFlag(Permission $permission): bool
    {
        return $permission->attributes->flags->update;
    }
}
