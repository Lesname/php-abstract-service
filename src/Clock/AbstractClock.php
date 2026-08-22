<?php

declare(strict_types=1);

namespace LesAbstractService\Clock;

use Override;
use LesValueObject\Number\Int\Date\Timestamp;

/**
 * @psalm-mutable
 */
abstract class AbstractClock implements Clock
{
    #[Override]
    public function timestamp(): Timestamp
    {
        return $this->milliTimestamp()->toTimestamp();
    }
}
