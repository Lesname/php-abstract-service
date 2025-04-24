<?php
declare(strict_types=1);

namespace LesAbstractService\Container\Mail;

use Psr\Container\ContainerInterface;

final class TemplateContainerFactory
{
    public function __invoke(ContainerInterface $container): TemplateContainer
    {
        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['templates']));

        $mappedTemplates = [];

        foreach ($config['templates'] as $class => $subTemplates) {
            assert(is_string($class));
            assert(is_array($subTemplates));

            foreach ($subTemplates as $origin => $reference) {
                assert(is_string($origin));

                assert(is_array($reference));
                assert(is_string($reference['type']));
                assert(is_string($reference['id']));

                $mappedTemplates["{$class}.{$origin}"] = [
                    'type' => $reference['type'],
                    'id' => $reference['id'],
                ];
            }
        }

        return new TemplateContainer($mappedTemplates);
    }
}
