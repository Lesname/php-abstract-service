<?php

declare(strict_types=1);

namespace LesAbstractService\Config\Provider;

use LesCache\Config\ConfigProvider as CacheConfigProvider;
use LesDatabase\Config\ConfigProvider as DatabaseConfigProvider;
use LesDomain\Config\ConfigProvider as DomainConfigProvider;
use LesDocumentor\Config\ConfigProvider as DocumentorConfigProvider;
use LesHttp\Config\ConfigProvider as HttpConfigProvider;
use LesHydrator\Config\ConfigProvider as HydratorConfigProvider;
use LesToken\Config\ConfigProvider as TokenConfigProvider;
use LesValidator\Config\ConfigProvider as ValidatorConfigProvider;

/**
 * @psalm-immutable
 */
final class AppConfigProvider
{
    /**
     * @return array<mixed>
     *
     * @psalm-mutation-free
     */
    public function __invoke(): array
    {
        $merger = new ConfigProviderMerger(
            [
                new BaseConfigProvider(),
                new CacheConfigProvider(),
                new DatabaseConfigProvider(),
                new DocumentorConfigProvider(),
                new DomainConfigProvider(),
                new HttpConfigProvider(),
                new HydratorConfigProvider(),
                new TokenConfigProvider(),
                new ValidatorConfigProvider(),
            ],
        );

        return $merger();
    }
}
