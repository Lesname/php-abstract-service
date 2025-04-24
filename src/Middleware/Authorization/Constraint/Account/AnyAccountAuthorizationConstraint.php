<?php
declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint\Account;

use Override;
use LesValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class AnyAccountAuthorizationConstraint extends AbstractAccountAuthorizationConstraint
{
    #[Override]
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        return true;
    }
}
