<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint\Account;

use LessHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LessValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

final class AnyAccountAuthorizationConstraint implements AuthorizationConstraint
{
    public function isAllowed(ServerRequestInterface $request): bool
    {
        $identity = $request->getAttribute('identity');
        assert($identity === null || $identity instanceof ForeignReference);

        return $identity instanceof ForeignReference
            && $identity->type->getValue() === 'identity.account';
    }
}
