<?php

declare(strict_types=1);

namespace LesAbstractService\Factory\Logger;

use Monolog\Logger;
use Rollbar\Rollbar;
use RuntimeException;
use Rollbar\RollbarLogger;
use Monolog\Handler\RollbarHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @deprecated merge into MonologFactory
 */
final class RollbarMonologDelegatorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): Logger
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['rollbar']));
        assert(is_array($config['rollbar']['config']));

        Rollbar::init($config['rollbar']['config']);

        $logger = $callback();
        assert($logger instanceof Logger);

        $rollbarLogger = Rollbar::logger();

        if (!$rollbarLogger instanceof RollbarLogger) {
            throw new RuntimeException();
        }

        $logger->pushHandler(new RollbarHandler($rollbarLogger));

        return $logger;
    }
}
