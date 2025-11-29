<?php

declare(strict_types=1);

namespace LesAbstractService\Middleware\Authorization\Constraint;

use Override;
use LesValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\AuthorizationConstraint;

abstract class AbstractTypeAuthorizationConstraint implements AuthorizationConstraint
{
    #[Override]
    public function isAllowed(ServerRequestInterface $request): bool
    {
        $identity = $request->getAttribute('identity');
        assert($identity instanceof ForeignReference || $identity === null);

        return $identity instanceof ForeignReference
            && $identity->type->value === $this->getAllowedType()
            && $this->isIdentityAllowed($request, $identity);
    }

    abstract protected function getAllowedType(): string;

    abstract protected function isIdentityAllowed(ServerRequestInterface $request, ForeignReference $identity): bool;
}
