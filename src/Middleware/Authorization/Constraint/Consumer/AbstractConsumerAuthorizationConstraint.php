<?php
declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Consumer;

use Override;
use LesAbstractService\Middleware\Authorization\Constraint\AbstractTypeAuthorizationConstraint;

abstract class AbstractConsumerAuthorizationConstraint extends AbstractTypeAuthorizationConstraint
{
    #[Override]
    protected function getAllowedType(): string
    {
        return 'identity.consumer';
    }
}
