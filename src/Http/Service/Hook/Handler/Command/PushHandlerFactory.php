<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Service\Hook\Handler\Command;

use LessAbstractClient\Requester\CurlRequester;
use LessQueue\Queue;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @deprecated
 */
final class PushHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PushHandler
    {
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config[PushHandler::class]));
        assert(is_string($config[PushHandler::class]['verifyUri']));
        assert(is_array($config[PushHandler::class]['eventQueueJobMap']));

        $requester = new CurlRequester($config[PushHandler::class]['verifyUri']);

        $queue = $container->get(Queue::class);
        assert($queue instanceof Queue);

        return new PushHandler(
            $responseFactory,
            $requester,
            $queue,
            $config[PushHandler::class]['eventQueueJobMap'],
        );
    }
}
