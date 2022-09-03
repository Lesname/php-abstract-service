<?php
declare(strict_types=1);

namespace LessAbstractService\Cli\Documentor;

use LessDocumentor\Route\RouteDocumentor;
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

        return new WriteCommand(
            $routeDocumentor,
            array_filter(
                $config['routes'],
                static fn (array $route): bool => !(isset($route['document']) && $route['document'] === false),
            ),
            $self['workDirectory'] . 'public/openapi.json',
            $self['baseUri'],
            $self['name'],
        );
    }
}
