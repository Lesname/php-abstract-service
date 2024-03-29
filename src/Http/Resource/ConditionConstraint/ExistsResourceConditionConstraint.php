<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\ConditionConstraint;

use Psr\Container\ContainerInterface;
use LessResource\Model\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\NotFoundExceptionInterface;
use LessValueObject\String\Exception\TooLong;
use Psr\Container\ContainerExceptionInterface;
use LessValueObject\String\Exception\TooShort;
use LessResource\Repository\ResourceRepository;
use LessValueObject\String\Format\Resource\Identifier;
use LessValueObject\String\Format\Exception\NotFormat;
use LessHttp\Middleware\Condition\Constraint\ConditionConstraint;
use LessHttp\Middleware\Condition\Constraint\Result\ConditionConstraintResult;
use LessHttp\Middleware\Condition\Constraint\Result\SatisfiedConditionConstraintResult;
use LessHttp\Middleware\Condition\Constraint\Result\UnsatisfiedConditionConstraintResult;

final class ExistsResourceConditionConstraint implements ConditionConstraint
{
    /**
     * @param array<string, string> $resourceRepositories
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $resourceRepositories,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     */
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
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $key = "{$method}:{$path}";

        assert(array_key_exists($key, $this->resourceRepositories));

        $resourceRepository = $this->container->get($this->resourceRepositories[$key]);
        assert($resourceRepository instanceof ResourceRepository);

        return $resourceRepository;
    }
}
