<?php

declare(strict_types=1);

namespace LesAbstractService\Clock;

use Override;
use LesValueObject\Number\Int\Date\MilliTimestamp;

/**
 * @psalm-mutable
 */
final class ActiveClock extends AbstractClock
{
    #[Override]
    public function milliTimestamp(): MilliTimestamp
    {
        return new MilliTimestamp((int)floor(microtime(true) * 1_000.0));
    }
}
