<?php
declare(strict_types=1);

namespace LessAbstractService\Queue\Worker\Hook;

use LessHook\Producer\Service\ProducerService;
use LessValueObject\Composite\ForeignReference;
use Psr\Container\ContainerInterface;

final class PushWorkerFactory
{
    public function __invoke(ContainerInterface $container): PushWorker
    {
        $config = $container->get('config');
        assert(is_array($config));

        assert(is_array($config[PushWorker::class]));
        assert(is_array($config[PushWorker::class]['producer']));
        assert(is_string($config[PushWorker::class]['producer']['type']));
        assert(is_string($config[PushWorker::class]['producer']['id']));

        $producerService = $container->get(ProducerService::class);
        assert($producerService instanceof ProducerService);

        return new PushWorker(
            $producerService,
            new ForeignReference(
                $config[PushWorker::class]['producer']['type'],
                $config[PushWorker::class]['producer']['id'],
            ),
        );
    }
}
