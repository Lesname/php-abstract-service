<?php
declare(strict_types=1);

namespace LessAbstractService\Cli\Queue;

use LessQueue\Queue;
use RuntimeException;
use LessQueue\Worker\Worker;
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

        $workerMap = [];

        foreach ($config['workers'] as $name => $worker) {
            if (!is_string($name)) {
                throw new RuntimeException();
            }

            if (!is_string($worker) && !$worker instanceof Worker) {
                throw new RuntimeException();
            }

            $workerMap[$name] = $worker;
        }

        return new ProcessCommand(
            $container,
            $logger,
            $queue,
            $workerMap,
        );
    }
}
