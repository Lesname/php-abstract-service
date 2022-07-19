<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Authorization\Constraint;

use LessHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LessValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractTypeAuthorizationConstraint implements AuthorizationConstraint
{
    public function isAllowed(ServerRequestInterface $request): bool
    {
        $identity = $request->getAttribute('identity');
        assert($identity instanceof ForeignReference || $identity === null);

        return $identity instanceof ForeignReference
            && $identity->type->getValue() === $this->getAllowedType()
            && $this->isIdentityAllowed($request, $identity);
    }

    abstract protected function getAllowedType(): string;

    abstract protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool;
}
