<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\ConditionConstraint;

use Psr\Container\ContainerInterface;
use LessResource\Model\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use LessResource\Repository\ResourceRepository;
use LessValueObject\String\Format\Resource\Identifier;
use LessHttp\Middleware\Condition\Constraint\ConditionConstraint;
use LessHttp\Middleware\Condition\Constraint\Result\ConditionConstraintResult;
use LessHttp\Middleware\Condition\Constraint\Result\SatisfiedConditionConstraintResult;
use LessHttp\Middleware\Condition\Constraint\Result\UnsatisfiedConditionConstraintResult;

final class ExistsResourceConditionConstraint implements ConditionConstraint
{
    public const ROUTE_OPTION_RESOURCE_REPOSITORY_KEY = 'resourceRepository';

    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    public function satisfies(ServerRequestInterface $request): ConditionConstraintResult
    {
        $body = $request->getParsedBody();
        assert(is_array($body));
        assert(is_string($body['id']));

        return !$this->getResourceRepository($request)->exists(new Identifier($body['id']))
            ? new UnsatisfiedConditionConstraintResult('resource.notExists', ['id' => $body['id']])
            : new SatisfiedConditionConstraintResult();
    }

    /**
     * @return ResourceRepository<ResourceModel>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getResourceRepository(ServerRequestInterface $request): ResourceRepository
    {
        $options = $request->getAttribute('routeOptions');
        assert(is_array($options));

        if (
            isset($options['resourceService'])
            && !isset($options[self::ROUTE_OPTION_RESOURCE_REPOSITORY_KEY])
            && is_string($options['resourceService'])
        ) {
            $options[self::ROUTE_OPTION_RESOURCE_REPOSITORY_KEY] = $options['resourceService'];
        }

        $resourceRepositoryKey = $options[self::ROUTE_OPTION_RESOURCE_REPOSITORY_KEY];
        assert(is_string($resourceRepositoryKey));

        $resourceService = $this->container->get($resourceRepositoryKey);
        assert($resourceService instanceof ResourceRepository);

        return $resourceService;
    }
}
