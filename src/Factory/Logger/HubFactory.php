<?php

declare(strict_types=1);

namespace LesAbstractService\Factory\Logger;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sentry\ClientBuilder;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

final class HubFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): HubInterface
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['sentry']));

        assert(is_string($config['sentry']['dsn']));
        assert(is_string($config['sentry']['release']));
        assert(is_string($config['sentry']['environment']));

        $client = ClientBuilder
            ::create(
                [
                    'dsn' => $config['sentry']['dsn'],
                    'release' => $config['sentry']['release'],
                    'environment' => $config['sentry']['environment'],
                ],
            )
            ->getClient();

        return new Hub($client);
    }
}
