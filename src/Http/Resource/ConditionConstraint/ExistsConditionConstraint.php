<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\ConditionConstraint;

use Override;
use RuntimeException;
use LesHttp\Router\Route\Route;
use Psr\Container\ContainerInterface;
use LesResource\Model\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\NotFoundExceptionInterface;
use LesValueObject\String\Exception\TooLong;
use Psr\Container\ContainerExceptionInterface;
use LesValueObject\String\Exception\TooShort;
use LesResource\Repository\ResourceRepository;
use LesHttp\Router\Route\Exception\OptionNotSet;
use LesValueObject\String\Format\Resource\Identifier;
use LesValueObject\String\Format\Exception\NotFormat;
use LesHttp\Middleware\AccessControl\Condition\Constraint\ConditionConstraint;
use LesHttp\Middleware\AccessControl\Condition\Constraint\Result\ConditionConstraintResult;
use LesHttp\Middleware\AccessControl\Condition\Constraint\Result\SatisfiedConditionConstraintResult;
use LesHttp\Middleware\AccessControl\Condition\Constraint\Result\UnsatisfiedConditionConstraintResult;

final class ExistsConditionConstraint implements ConditionConstraint
{
    public function __construct(private readonly ContainerInterface $container)
    {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     * @throws OptionNotSet
     */
    #[Override]
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
     * @throws OptionNotSet
     * @throws ContainerExceptionInterface
     */
    protected function getResourceRepository(ServerRequestInterface $request): ResourceRepository
    {
        $route = $request->getAttribute('route');

        if (!$route instanceof Route) {
            throw new RuntimeException();
        }

        $resourceRepository = $route->getOption('resourceRepository');
        assert(is_string($resourceRepository));

        $resourceRepository = $this->container->get($resourceRepository);
        assert($resourceRepository instanceof ResourceRepository);

        return $resourceRepository;
    }
}
