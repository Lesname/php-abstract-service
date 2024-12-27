<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository;

use RuntimeException;
use LessHydrator\Hydrator;
use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;

final class DbalPermissionsRepositoryFactory
{
    public function __invoke(ContainerInterface $container): DbalPermissionsRepository
    {
        $connection = $container->get(Connection::class);
        assert($connection instanceof Connection);

        $hydrator = $container->get(Hydrator::class);
        assert($hydrator instanceof Hydrator);

        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['self']));
        assert(is_string($config['self']['name']));

        if (preg_match('/^[a-z]+\.(?<serviceName>[a-zA-Z]+)\.[a-z]+$/', $config['self']['name'], $matches) !== 1) {
            throw new RuntimeException();
        }

        return new DbalPermissionsRepository(
            $matches['serviceName'],
            $connection,
            $hydrator,
        );
    }
}
