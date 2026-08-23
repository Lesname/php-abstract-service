<?php

declare(strict_types=1);

namespace LesAbstractServiceTest\Config\Provider;

use LesAbstractService\Config\Provider\ConfigProviderMerger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ConfigProviderMerger::class)]
class ConfigProviderMergerTest extends TestCase
{
    public function testMerge(): void
    {
        $merger = new ConfigProviderMerger(
            [
                function (): array {
                    return [
                        'foo' => 'bar',
                        'composite' => [
                            'biz' => 1,
                        ],
                        'list' => [
                            1,
                            2,
                        ],
                    ];
                },
                function (): array {
                    return [
                        'fiz' => 'bar',
                        'composite' => [
                            'bar' => 1,
                        ],
                        'list' => [
                            3,
                            4,
                        ],
                    ];
                }
            ],
        );

        self::assertSame(
            [
                'foo' => 'bar',
                'composite' => [
                    'biz' => 1,
                    'bar' => 1,
                ],
                'list' => [
                    1,
                    2,
                    3,
                    4,
                ],
                'fiz' => 'bar',
            ],
            $merger(),
        );
    }
}
