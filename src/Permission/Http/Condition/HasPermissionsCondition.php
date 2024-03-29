<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Http\Condition;

use Psr\Http\Message\ServerRequestInterface;
use LessValueObject\Composite\ForeignReference;
use LessHttp\Middleware\Condition\Constraint\ConditionConstraint;
use LessAbstractService\Permission\Repository\PermissionsRepository;
use LessHttp\Middleware\Condition\Constraint\Result\ConditionConstraintResult;
use LessHttp\Middleware\Condition\Constraint\Result\SatisfiedConditionConstraintResult;
use LessHttp\Middleware\Condition\Constraint\Result\UnsatisfiedConditionConstraintResult;

final class HasPermissionsCondition implements ConditionConstraint
{
    public function __construct(private readonly PermissionsRepository $permissionsRepository)
    {}

    public function satisfies(ServerRequestInterface $request): ConditionConstraintResult
    {
        $body = $request->getParsedBody();
        assert(is_array($body));
        assert(is_array($body['identity']));
        assert(is_string($body['identity']['type']));
        assert(is_string($body['identity']['id']));

        $identity = ForeignReference::fromArray(
            [
                'type' => $body['identity']['type'],
                'id' => $body['identity']['id'],
            ],
        );

        return !$this->permissionsRepository->existsWithIdentity($identity)
            ? new UnsatisfiedConditionConstraintResult('permission.hasNoPermissions')
            : new SatisfiedConditionConstraintResult();
    }
}
