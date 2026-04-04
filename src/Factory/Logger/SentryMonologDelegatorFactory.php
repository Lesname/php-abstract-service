<?php

declare(strict_types=1);

namespace LesAbstractService\Factory\Logger;

use Monolog\Logger;
use Sentry\SentrySdk;
use Sentry\Logs\LogLevel;
use Sentry\Monolog\LogsHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sentry\State\HubInterface;

final class SentryMonologDelegatorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): Logger
    {
        $logger = $callback();
        assert($logger instanceof Logger);

        $hub = $container->get(HubInterface::class);
        assert($hub instanceof HubInterface);

        SentrySdk::setCurrentHub($hub);

        $logger->pushHandler(
            new LogsHandler(
                LogLevel::info(),
            ),
        );

        return $logger;
    }
}
