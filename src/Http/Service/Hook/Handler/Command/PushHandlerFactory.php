<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Service\Hook\Handler\Command;

use a;
use LessAbstractClient\Requester\CurlRequester;
use LessHydrator\Hydrator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class PushHandlerFactory
{
    public function __invoke(ContainerInterface $container): PushHandler
    {
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config[PushHandler::class]));
        assert(is_string($config[PushHandler::class]['verifyUri']));

        $requester = new CurlRequester($config[PushHandler::class]['verifyUri']);

        $hydrator = $container->get(Hydrator::class);
        assert($hydrator instanceof Hydrator);

        return new PushHandler($responseFactory, $requester, $hydrator);
    }
}
