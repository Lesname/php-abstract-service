<?php

declare(strict_types=1);

namespace LesAbstractServiceTest\Clock;

use LesAbstractService\Clock\ActiveClock;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActiveClock::class)]
class ActiveClockTest extends TestCase
{
    public function testMilliTimestamp(): void
    {
        $clock = new ActiveClock();

        $start = (int)floor(microtime(true) * 1000);
        $result = $clock->milliTimestamp();
        $end = (int)ceil(microtime(true) * 1000);

        self::assertTrue($start <= $result->value);
        self::assertTrue($end >= $result->value);
    }
}
