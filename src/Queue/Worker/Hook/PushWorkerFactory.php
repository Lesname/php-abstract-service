<?php
declare(strict_types=1);

namespace LessAbstractService\Queue\Worker\Hook;

use LessHook\Producer\Service\ProducerService;
use LessValueObject\Composite\ForeignReference;
use LessValueObject\String\Exception\TooLong;
use LessValueObject\String\Exception\TooShort;
use LessValueObject\String\Format\Exception\NotFormat;
use LessValueObject\String\Format\Resource\Identifier;
use LessValueObject\String\Format\Resource\Type;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @deprecated
 *
 * @psalm-suppress DeprecatedClass
 */
final class PushWorkerFactory
{
    /**
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress DeprecatedClass
     */
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
                new Type($config[PushWorker::class]['producer']['type']),
                new Identifier($config[PushWorker::class]['producer']['id']),
            ),
        );
    }
}
