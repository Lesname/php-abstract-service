<?php

declare(strict_types=1);

namespace LesAbstractService\Mezzio\Listener;

use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;

/**
 * @deprecated
 */
final class ErrorHandlerDelegatorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): ErrorHandler
    {
        $errorHandler = $callback();
        assert($errorHandler instanceof ErrorHandler);

        $logger = $container->get(LoggerInterface::class);
        assert($logger instanceof LoggerInterface);

        $errorHandler->attachListener(static fn (Throwable $e) => $logger->error($e->getMessage(), ['exception' => $e]));

        return $errorHandler;
    }
}
