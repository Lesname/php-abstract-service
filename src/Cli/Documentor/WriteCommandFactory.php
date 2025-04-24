<?php
declare(strict_types=1);

namespace LesAbstractService\Cli\Documentor;

use RuntimeException;
use LesDocumentor\Route\RouteDocumentor;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class WriteCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function __invoke(ContainerInterface $container): WriteCommand
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['routes']));

        assert(is_array($config['self']));
        $self = $config['self'];

        assert(is_string($self['workDirectory']));
        assert(is_string($self['baseUri']));
        assert(is_string($self['name']));

        $routeDocumentor = $container->get(RouteDocumentor::class);
        assert($routeDocumentor instanceof RouteDocumentor);

        $routes = [];

        foreach ($config['routes'] as $key => $route) {
            if (!is_array($route)) {
                throw new RuntimeException();
            }

            if (isset($route['document']) && $route['document'] === false) {
                continue;
            }

            $routes[$key] = $route;
        }

        return new WriteCommand(
            $routeDocumentor,
            $routes,
            $self['workDirectory'] . 'public/openapi.json',
            $self['baseUri'],
            $self['name'],
        );
    }
}
