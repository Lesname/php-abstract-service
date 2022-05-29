<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Prerequisite\Resource;

use Interop\Container\ContainerInterface;

final class ResourcePrerequisiteFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName): AbstractResourcePrerequisite
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['routes']));
        assert(is_subclass_of($requestedName, AbstractResourcePrerequisite::class));

        return new $requestedName($container, $this->parseResourceServices($config['routes']));
    }

    /**
     * @param array<mixed> $routes
     *
     * @return array<mixed>
     */
    private function parseResourceServices(array $routes): array
    {
        $resourceServices = [];

        foreach ($routes as $key => $route) {
            assert(is_array($route));

            if (!isset($route['resourceService'])) {
                continue;
            }

            assert(is_string($route['resourceService']));
            $resourceServices[$key] = $route['resourceService'];
        }

        return $resourceServices;
    }
}
