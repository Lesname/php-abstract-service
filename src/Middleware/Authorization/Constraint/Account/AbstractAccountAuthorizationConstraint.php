<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Account;

use LessAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

abstract class AbstractAccountAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    protected function getAllowedType(): string
    {
        return 'identity.account';
    }
}
