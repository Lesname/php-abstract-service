<?php

declare(strict_types=1);

namespace LesAbstractService\Config\Provider;

final class ConfigProviderMerger
{
    /**
     * @param array<callable(): array> $providers
     */
    public function __construct(private readonly array $providers)
    {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $config = [];

        foreach ($this->providers as $provider) {
            if ($config === []) {
                $config = $provider();

                continue;
            }

            $config = $this->mergeArray($config, $provider());
        }

        return $config;
    }

    private function mergeArray(array $left, array $right): array
    {
        foreach ($right as $key => $value) {
            if (is_string($key)) {
                $left[$key] = isset($left[$key]) && is_array($left[$key]) && is_array($value)
                    ? $this->mergeArray($left[$key], $value)
                    : $value;
            } else {
                $left[] = $value;
            }
        }

        return $left;
    }
}
