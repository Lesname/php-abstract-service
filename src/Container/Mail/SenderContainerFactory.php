<?php
declare(strict_types=1);

namespace LessAbstractService\Container\Mail;

use Psr\Container\ContainerInterface;

final class SenderContainerFactory
{
    public function __invoke(ContainerInterface $container): SenderContainer
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['senders']));

        $senders = [];

        foreach ($config['senders'] as $origin => $reference) {
            assert(is_string($origin));

            assert(is_array($reference));
            assert(is_string($reference['type']));
            assert(is_string($reference['id']));

            $senders[$origin] = [
                'type' => $reference['type'],
                'id' => $reference['id'],
            ];
        }

        return new SenderContainer($senders);
    }
}
