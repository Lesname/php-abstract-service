<?php
declare(strict_types=1);

namespace LessAbstractService\Cli\Queue;

use LessQueue\Queue;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

final class ProcessCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessCommand
    {
        $logger = $container->get(LoggerInterface::class);
        assert($logger instanceof LoggerInterface);

        $queue = $container->get(Queue::class);
        assert($queue instanceof Queue);

        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['workers']));

        return new ProcessCommand(
            $container,
            $logger,
            $queue,
            $config['workers'],
        );
    }
}
