<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Prerequisite\Resource;

use LessHttp\Middleware\Prerequisite\Constraint\PrerequisiteConstraint;
use LessResource\Model\ResourceModel;
use LessResource\Repository\ResourceRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractResourcePrerequisite implements PrerequisiteConstraint
{
    /**
     * @param array<string, string> $resourceRepositories
     */
    final public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $resourceRepositories,
    ) {}

    /**
     * @return ResourceRepository<ResourceModel>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedReturnTypeCoercion
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
