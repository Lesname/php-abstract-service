<?php
declare(strict_types=1);

namespace LessAbstractService\Factory\Logger;

use Monolog\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sentry\Monolog\Handler;
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

        $logger->pushHandler(new Handler($hub));

        return $logger;
    }
}
