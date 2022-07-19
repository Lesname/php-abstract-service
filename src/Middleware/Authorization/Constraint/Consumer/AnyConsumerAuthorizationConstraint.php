<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Consumer;

use LessValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class AnyConsumerAuthorizationConstraint extends AbstractConsumerAuthorizationConstraint
{
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        return true;
    }
}
