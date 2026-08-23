<?php

declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Account;

use Override;
use LesAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

/**
 * @deprecated no replacement
 */
abstract class AbstractAccountAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected function getAllowedType(): string
    {
        return 'identity.account';
    }
}
