<?php
declare(strict_types=1);

namespace LessAbstractService\Middleware\Throttle;

use LessHttp\Middleware\Throttle\Parameter\By;

final class ThrottleBuilder
{
    private ?string $action = null;

    private ?By $by = null;

    /**
     * @param array<int, int> $multiples
     */
    public function __construct(
        private int $basePoints,
        private int $baseDuration,
        private array $multiples = [
            15 => 10,
            60 => 30,
            60 * 24 => 10 * 3 * 8,
        ],
    ) {
    }

    public function withAction(?string $action): self
    {
        $clone = clone $this;
        $clone->action = $action;

        return $clone;
    }

    public function withBy(?By $by): self
    {
        $clone = clone $this;
        $clone->by = $by;

        return $clone;
    }

    public function withBasePoints(int $basePoints): self
    {
        $clone = clone $this;
        $clone->basePoints = $basePoints;

        return $clone;
    }

    public function withBaseDuration(int $baseDuration): self
    {
        $clone = clone $this;
        $clone->baseDuration = $baseDuration;

        return $clone;
    }

    /**
     * @param array<int, int> $multiples
     */
    public function withMultiples(array $multiples): self
    {
        $clone = clone $this;
        $clone->multiples = $multiples;

        return $clone;
    }

    /**
     * @return iterable<int, array{action: string | null, by: By | null, duration: int, points: int}>
     */
    public function build(): iterable
    {
        yield [
            'action' => $this->action,
            'by' => $this->by,
            'duration' => $this->baseDuration,
            'points' => $this->basePoints,
        ];

        foreach ($this->multiples as $multipleDuration => $multiplePoints) {
            yield [
                'action' => $this->action,
                'by' => $this->by,
                'duration' => $this->baseDuration * $multipleDuration,
                'points' => $this->basePoints * $multiplePoints,
            ];
        }
    }
}
