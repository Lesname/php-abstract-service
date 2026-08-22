<?php

declare(strict_types=1);

namespace LesAbstractServiceTest\Clock;

use LesAbstractService\Clock\StaticClock;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use LesValueObject\Number\Int\Date\MilliTimestamp;

#[CoversClass(StaticClock::class)]
class StaticClockTest extends TestCase
{
    public function testMilliTimestamp(): void
    {
        $milliTimestamp = new MilliTimestamp(123);

        $clock = new StaticClock($milliTimestamp);

        self::assertSame($milliTimestamp, $clock->milliTimestamp());
    }
}
