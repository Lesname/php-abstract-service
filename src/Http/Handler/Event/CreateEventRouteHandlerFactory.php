<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Handler\Event;

use LessDomain\Event\Store\Store;
use LessDomain\Identifier\IdentifierService;
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

        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $streamFactory = $container->get(StreamFactoryInterface::class);
        assert($streamFactory instanceof StreamFactoryInterface);

        $identifierService = $container->get(IdentifierService::class);
        assert($identifierService instanceof IdentifierService);

        $hydrator = $container->get(Hydrator::class);
        assert($hydrator instanceof Hydrator);

        $store = $container->get(Store::class);
        assert($store instanceof Store);

        return new CreateEventRouteHandler(
            $responseFactory,
            $streamFactory,
            $identifierService,
            $hydrator,
            $store,
            $config['routes'],
        );
    }
}
