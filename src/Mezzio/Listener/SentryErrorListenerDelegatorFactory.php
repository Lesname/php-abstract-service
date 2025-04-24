<?php
declare(strict_types=1);

namespace LesAbstractService\Mezzio\Listener;

use Sentry\State\HubInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;

final class SentryErrorListenerDelegatorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): ErrorHandler
    {
        $errorHandler = $callback();
        assert($errorHandler instanceof ErrorHandler);

        $hub = $container->get(HubInterface::class);
        assert($hub instanceof HubInterface);

        $listener = new SentryErrorListener($hub);
        $errorHandler->attachListener($listener);

        return $errorHandler;
    }
}
