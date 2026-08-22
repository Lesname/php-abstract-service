<?php

declare(strict_types=1);

namespace LesAbstractService\Clock;

use LesValueObject\Number\Int\Date\Timestamp;
use LesValueObject\Number\Int\Date\MilliTimestamp;

/**
 * @psalm-mutable
 */
interface Clock
{
    /**
     * @psalm-impure
     */
    public function timestamp(): Timestamp;

    /**
     * @psalm-impure
     */
    public function milliTimestamp(): MilliTimestamp;
}
