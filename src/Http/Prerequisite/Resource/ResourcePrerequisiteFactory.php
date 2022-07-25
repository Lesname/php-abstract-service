<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Prerequisite\Resource;

use Interop\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ResourcePrerequisiteFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $requestedName): AbstractResourcePrerequisite
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['routes']));
        assert(is_subclass_of($requestedName, AbstractResourcePrerequisite::class));

        return new $requestedName($container, $this->parseResourceRepositories($config['routes']));
    }

    /**
     * @param array<mixed> $routes
     *
     * @return array<mixed>
     */
    private function parseResourceRepositories(array $routes): array
    {
        $resourceRepositories = [];

        foreach ($routes as $key => $route) {
            assert(is_array($route));

            if (!isset($route['resourceRepository'])) {
                continue;
            }

            assert(is_string($route['resourceRepository']));
            $resourceRepositories[$key] = $route['resourceRepository'];
        }

        return $resourceRepositories;
    }
}
