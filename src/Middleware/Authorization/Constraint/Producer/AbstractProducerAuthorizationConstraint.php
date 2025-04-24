<?php
declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Producer;

use Override;
use LesAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

abstract class AbstractProducerAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    #[Override]
    protected function getAllowedType(): string
    {
        return 'identity.producer';
    }
}
