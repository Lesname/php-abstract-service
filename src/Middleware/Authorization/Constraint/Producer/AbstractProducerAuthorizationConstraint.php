<?php

declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Producer;

use Override;
use LesAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

abstract class AbstractProducerAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected function getAllowedType(): string
    {
        return 'identity.producer';
    }
}
