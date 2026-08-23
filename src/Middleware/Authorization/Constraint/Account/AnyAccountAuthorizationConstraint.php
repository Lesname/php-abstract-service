<?php

declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Account;

use Override;
use LesValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @deprecated no replacement
 */
final class AnyAccountAuthorizationConstraint extends AbstractAccountAuthorizationConstraint
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
