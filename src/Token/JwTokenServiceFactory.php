<?php
declare(strict_types=1);

namespace LessAbstractService\Token;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class JwTokenServiceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): JwTokenService
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config[JwTokenService::class]));

        $settings = $config[JwTokenService::class];
        assert(is_string($settings['keyIdentifier']));
        assert(is_string($settings['keyFile']));

        return new JwTokenService(
            $settings['keyIdentifier'],
            $settings['keyFile'],
        );
    }
}
