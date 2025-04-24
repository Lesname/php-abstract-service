<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Http\Condition;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use LesValueObject\Composite\ForeignReference;
use LesHttp\Middleware\Condition\Constraint\ConditionConstraint;
use LesAbstractService\Permission\Repository\PermissionsRepository;
use LesHttp\Middleware\Condition\Constraint\Result\ConditionConstraintResult;
use LesHttp\Middleware\Condition\Constraint\Result\SatisfiedConditionConstraintResult;
use LesHttp\Middleware\Condition\Constraint\Result\UnsatisfiedConditionConstraintResult;

final class HasNoPermissionsCondition implements ConditionConstraint
{
    public function __construct(private readonly PermissionsRepository $permissionsRepository)
    {}

    #[Override]
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

        return $this->permissionsRepository->existsWithIdentity($identity)
            ? new UnsatisfiedConditionConstraintResult('permission.hasPermissions')
            : new SatisfiedConditionConstraintResult();
    }
}
