<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Producer;

use LessValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class AnyProducerAuthorizationConstraint extends AbstractProducerAuthorizationConstraint
{
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        return true;
    }
}
