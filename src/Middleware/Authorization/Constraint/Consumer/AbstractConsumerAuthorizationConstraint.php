<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Consumer;

use LessAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

abstract class AbstractConsumerAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    protected function getAllowedType(): string
    {
        return 'identity.consumer';
    }
}
