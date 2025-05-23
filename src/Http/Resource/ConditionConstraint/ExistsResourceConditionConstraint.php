<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\ConditionConstraint;

use Override;
use RuntimeException;
use Psr\Container\ContainerInterface;
use LesResource\Model\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\NotFoundExceptionInterface;
use LesValueObject\String\Exception\TooLong;
use Psr\Container\ContainerExceptionInterface;
use LesValueObject\String\Exception\TooShort;
use LesResource\Repository\ResourceRepository;
use LesDocumentor\Route\Document\Property\Method;
use LesValueObject\String\Format\Resource\Identifier;
use LesValueObject\String\Format\Exception\NotFormat;
use LesHttp\Middleware\Condition\Constraint\ConditionConstraint;
use LesHttp\Middleware\Condition\Constraint\Result\ConditionConstraintResult;
use LesHttp\Middleware\Condition\Constraint\Result\SatisfiedConditionConstraintResult;
use LesHttp\Middleware\Condition\Constraint\Result\UnsatisfiedConditionConstraintResult;

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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getResourceRepository(ServerRequestInterface $request): ResourceRepository
    {
        $method = strtolower($request->getMethod());
        $path = $request->getUri()->getPath();
        $key = "{$method}:{$path}";

        if (array_key_exists($key, $this->resourceRepositories)) {
            $resourceRepository = $this->container->get($this->resourceRepositories[$key]);
            assert($resourceRepository instanceof ResourceRepository);

            return $resourceRepository;
        } elseif ($method === Method::Post->value) {
            $tryMethods = [
                Method::Query->value,
                Method::Delete->value,
                Method::Patch->value,
                Method::Put->value,
            ];

            foreach ($tryMethods as $tryMethod) {
                $key = "{$tryMethod}:{$path}";

                if (array_key_exists($key, $this->resourceRepositories)) {
                    $resourceRepository = $this->container->get($this->resourceRepositories[$key]);
                    assert($resourceRepository instanceof ResourceRepository);

                    return $resourceRepository;
                }
            }
        }

        throw new RuntimeException();
    }
}
