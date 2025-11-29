<?php

declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Producer;

use Override;
use LesValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class AnyProducerAuthorizationConstraint extends AbstractProducerAuthorizationConstraint
{
    #[Override]
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        return true;
    }
}
