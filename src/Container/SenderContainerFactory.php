<?php
declare(strict_types=1);

namespace LessAbstractService\Container;

use Psr\Container\ContainerInterface;
use LessValueObject\Composite\ForeignReference;

final class SenderContainerFactory
{
    public function __invoke(ContainerInterface $container): SenderContainer
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['senders']));

        $senders = [];

        foreach ($config['senders'] as $id => $sender) {
            assert(is_string($id));

            assert(is_array($sender));
            assert(is_string($sender['type']));
            assert(is_string($sender['id']));

            $senders[$id] = ForeignReference::fromArray(
                [
                    'type' => $sender['type'],
                    'id' => $sender['id'],
                ],
            );
        }

        return new SenderContainer($senders);
    }
}
