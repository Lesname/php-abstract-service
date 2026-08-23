<?php

declare(strict_types=1);

namespace LesAbstractService\Factory\Symfony\Translator;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\LoggingTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Translation\Loader\PhpFileLoader;

final class TranslatorFactory
{
    public function __invoke(ContainerInterface $container): TranslatorInterface
    {
        $config = $container->get('config');

        assert(is_array($config));
        assert(is_array($config['translator']));
        assert(is_string($config['translator']['defaultLocale']));

        $translator = new Translator($config['translator']['defaultLocale']);
        $translator->addLoader('file', new PhpFileLoader());

        assert(is_array($config['translator']['translation']));

        foreach ($config['translator']['translation'] as $locale => $files) {
            assert(is_string($locale));
            assert(is_array($files));

            foreach ($files as $file) {
                assert(is_string($file));

                $translator->addResource('file', $file, $locale);
            }
        }

        $logger = $container->get(LoggerInterface::class);
        assert($logger instanceof LoggerInterface);

        return new LoggingTranslator(
            $translator,
            $logger,
        );
    }
}
