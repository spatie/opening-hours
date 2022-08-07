<?php

namespace Spatie\OpeningHours\Test;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\PreciseTime;

class PreciseTimeTest extends TestCase
{
    /** @test */
    public function it_can_be_formatted()
    {
        $date = new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto');
        $this->assertSame(
            '2022-08-08 05:32:58.123456 Europe/Berlin',
            PreciseTime::fromDateTime($date)->format('Y-m-d H:i:s.u e', 'Europe/Berlin')
        );
        $this->assertSame(
            '2022-08-08 12:32:58.123456 Asia/Tokyo',
            PreciseTime::fromDateTime($date)->format('Y-m-d H:i:s.u e', new DateTimeZone('Asia/Tokyo'))
        );
    }

    /** @test */
    public function it_can_return_original_datetime()
    {
        $date = new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto');
        $this->assertSame($date, PreciseTime::fromDateTime($date)->toDateTime());
        $this->assertSame('2021-11-25 23:32:58'.(PHP_VERSION < 7.1 ? '' : '.123456').' Asia/Tokyo', PreciseTime::fromDateTime($date)->toDateTime(
            new DateTimeImmutable('2021-11-25 15:02:03.987654 Asia/Tokyo')
        )->format('Y-m-d H:i:s'.(PHP_VERSION < 7.1 ? '' : '.u').' e'));
    }

    /** @test */
    public function it_can_return_diff()
    {
        $date = new DateTimeImmutable('2021-08-07 23:32:58.123456 America/Toronto');
        $this->assertSame(
            '02 29 05 '.(PHP_VERSION < 7.1 ? '%F' : '864198'),
            PreciseTime::fromDateTime($date)->diff(PreciseTime::fromDateTime(
                new DateTimeImmutable('2022-11-25 15:02:03.987654 Asia/Tokyo')
            ))->format('%H %I %S %F')
        );
    }

    /** @test */
    public function it_can_be_compared()
    {
        $date = new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto');
        $this->assertSame($date, PreciseTime::fromDateTime($date)->toDateTime());
        $this->assertTrue(PreciseTime::fromDateTime(
            new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto')
        )->isSame(PreciseTime::fromDateTime(
            new DateTimeImmutable('2021-11-25 23:32:58.123456 Asia/Tokyo')
        )));
        $this->assertFalse(PreciseTime::fromDateTime(
            new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto')
        )->isSame(PreciseTime::fromDateTime(
            new DateTimeImmutable('2022-08-07 23:32:58.123457 America/Toronto')
        )));
    }

    /** @test */
    public function it_can_output_hours_and_minutes()
    {
        $date = PreciseTime::fromString('2022-08-07 23:32:58.123456 America/Toronto');
        $this->assertSame(23, $date->hours());
        $this->assertSame(32, $date->minutes());
    }
}
