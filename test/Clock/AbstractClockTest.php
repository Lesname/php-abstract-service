<?php

declare(strict_types=1);

namespace LesAbstractServiceTest\Clock;

use Override;
use LesAbstractService\Clock\AbstractClock;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use LesValueObject\Number\Int\Date\Timestamp;
use LesValueObject\Number\Int\Date\MilliTimestamp;

#[CoversClass(AbstractClock::class)]
class AbstractClockTest extends TestCase
{
    public function testTimestamp(): void
    {
        $clock = new class extends AbstractClock {
            #[Override]
            public function milliTimestamp(): MilliTimestamp
            {
                return new MilliTimestamp(123_456);
            }
        };

        self::assertEquals(
            new Timestamp(123),
            $clock->timestamp(),
        );
    }
}
