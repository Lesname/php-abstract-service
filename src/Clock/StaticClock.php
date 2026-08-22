<?php

declare(strict_types=1);

namespace LesAbstractService\Clock;

use Override;
use LesValueObject\Number\Int\Date\MilliTimestamp;

/**
 * @psalm-mutable
 */
final class StaticClock extends AbstractClock
{
    /**
     * @psalm-pure
     */
    public function __construct(private readonly MilliTimestamp $milliTimestamp)
    {}

    #[Override]
    public function milliTimestamp(): MilliTimestamp
    {
        return $this->milliTimestamp;
    }
}
