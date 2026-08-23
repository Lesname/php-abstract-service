<?php

declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Producer;

use Override;
use LesValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @deprecated no replacement
 */
final class AnyProducerAuthorizationConstraint extends AbstractProducerAuthorizationConstraint
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        return true;
    }
}
