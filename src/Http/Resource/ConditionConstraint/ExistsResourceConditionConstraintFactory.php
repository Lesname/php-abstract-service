<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\ConditionConstraint;

use Interop\Container\Containerinterface;

final class ExistsResourceConditionConstraintFactory
{
    public function __invoke(ContainerInterface $container): ExistsResourceConditionConstraint
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['routes']));

        return new ExistsResourceConditionConstraint($container, $this->parseResourceRepositories($config['routes']));
    }

    /**
     * @param array<mixed> $routes
     *
     * @return array<string, string>
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
            assert(is_string($key));

            $resourceRepositories[$key] = $route['resourceRepository'];
        }

        return $resourceRepositories;
    }
}
