<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Handler\Event;

use LessDomain\Event\Store\Store;
use LessHydrator\Hydrator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @deprecated use Resource namespaced
 */
final class UpdateEventRouteHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): UpdateEventRouteHandler
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['routes']));

        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $hydrator = $container->get(Hydrator::class);
        assert($hydrator instanceof Hydrator);

        $store = $container->get(Store::class);
        assert($store instanceof Store);

        return new UpdateEventRouteHandler(
            $responseFactory,
            $hydrator,
            $store,
            $config['routes'],
        );
    }
}
