<?php

declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Account;

use Override;
use LesAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

abstract class AbstractAccountAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    #[Override]
    protected function getAllowedType(): string
    {
        return 'identity.account';
    }
}
