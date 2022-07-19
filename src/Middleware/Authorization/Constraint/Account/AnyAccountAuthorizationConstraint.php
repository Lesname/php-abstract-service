<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Account;

use LessValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class AnyAccountAuthorizationConstraint extends AbstractAccountAuthorizationConstraint
{
    protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool
    {
        return true;
    }
}
