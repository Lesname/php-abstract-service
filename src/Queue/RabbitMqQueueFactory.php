<?php
declare(strict_types=1);

namespace LessAbstractService\Queue;

use Doctrine\DBAL\Connection;
use LessQueue\RabbitMqQueue;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class RabbitMqQueueFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RabbitMqQueue
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config[AMQPStreamConnection::class]));

        assert(is_string($config[AMQPStreamConnection::class]['host']));
        assert(is_int($config[AMQPStreamConnection::class]['port']));
        assert(is_string($config[AMQPStreamConnection::class]['user']));
        assert(is_string($config[AMQPStreamConnection::class]['pass']));
        assert(is_string($config[AMQPStreamConnection::class]['vhost']));

        $connection = new AMQPStreamConnection(
            $config[AMQPStreamConnection::class]['host'],
            $config[AMQPStreamConnection::class]['port'],
            $config[AMQPStreamConnection::class]['user'],
            $config[AMQPStreamConnection::class]['pass'],
            $config[AMQPStreamConnection::class]['vhost'],
        );

        $database = $container->get(Connection::class);
        assert($database instanceof Connection);

        return new RabbitMqQueue($connection, $database);
    }
}
