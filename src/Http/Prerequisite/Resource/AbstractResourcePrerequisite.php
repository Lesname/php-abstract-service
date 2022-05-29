<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Prerequisite\Resource;

use LessHttp\Middleware\Prerequisite\Constraint\PrerequisiteConstraint;
use LessResource\Model\ResourceModel;
use LessResource\Service\ResourceService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractResourcePrerequisite implements PrerequisiteConstraint
{
    /**
     * @param array<string, string> $resourceServices
     */
    final public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $resourceServices,
    ) {}

    /**
     * @return ResourceService<ResourceModel>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    protected function getResourceService(ServerRequestInterface $request): ResourceService
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $key = "{$method}:{$path}";

        assert(array_key_exists($key, $this->resourceServices));

        $resourceService = $this->container->get($this->resourceServices[$key]);
        assert($resourceService instanceof ResourceService);

        return $resourceService;
    }
}
