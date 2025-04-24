<?php
declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Consumer;

use Override;
use LesValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class AnyConsumerAuthorizationConstraint extends AbstractConsumerAuthorizationConstraint
{
    #[Override]
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        return true;
    }
}
