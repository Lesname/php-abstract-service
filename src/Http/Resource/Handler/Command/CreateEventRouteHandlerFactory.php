<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Handler\Command;

use RuntimeException;
use LessDomain\Event\Store\Store;
use LessDomain\Identifier\Generator\IdentifierGenerator;
use LessHydrator\Hydrator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class CreateEventRouteHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): CreateEventRouteHandler
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['routes']));

        assert(is_array($config['self']));
        assert(is_string($config['self']['name']));

        if (preg_match('/^[a-z]+\.(?<projectName>[a-z]+)\.(prod|dev|local)$/i', $config['self']['name'], $matches) !== 1) {
            throw new RuntimeException('Cannot find project name');
        }

        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $streamFactory = $container->get(StreamFactoryInterface::class);
        assert($streamFactory instanceof StreamFactoryInterface);

        $identifierGenerator = $container->get(IdentifierGenerator::class);
        assert($identifierGenerator instanceof IdentifierGenerator);

        $hydrator = $container->get(Hydrator::class);
        assert($hydrator instanceof Hydrator);

        $store = $container->get(Store::class);
        assert($store instanceof Store);

        return new CreateEventRouteHandler(
            $responseFactory,
            $streamFactory,
            $identifierGenerator,
            $matches['projectName'],
            $hydrator,
            $store,
            $config['routes'],
        );
    }
}
