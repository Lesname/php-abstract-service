<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Producer;

use LessAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

abstract class AbstractProducerAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    protected function getAllowedType(): string
    {
        return 'identity.producer';
    }
}
