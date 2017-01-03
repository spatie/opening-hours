<?php

namespace Spatie\OpeningHours\Test;

use Spatie\OpeningHours\Time;
use PHPUnit_Framework_TestCase;
use Spatie\OpeningHours\TimeRange;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Exceptions\OverlappingTimeRanges;

class OpeningHoursForDayTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created_from_an_array_of_time_range_strings()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

        $this->assertCount(2, $openingHoursForDay);

        $this->assertInstanceOf(TimeRange::class, $openingHoursForDay[0]);
        $this->assertEquals('09:00-12:00', (string) $openingHoursForDay[0]);

        $this->assertInstanceOf(TimeRange::class, $openingHoursForDay[1]);
        $this->assertEquals('13:00-18:00', (string) $openingHoursForDay[1]);
    }

    /** @test */
    public function it_cant_be_created_when_time_ranges_overlap()
    {
        $this->expectException(OverlappingTimeRanges::class);

        OpeningHoursForDay::fromStrings(['09:00-18:00', '14:00-20:00']);
    }

    /** @test */
    public function it_can_determine_whether_its_open_at_a_time()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-18:00']);

        $this->assertTrue($openingHoursForDay->isOpenAt(Time::fromString('09:00')));
        $this->assertFalse($openingHoursForDay->isOpenAt(Time::fromString('08:00')));
        $this->assertFalse($openingHoursForDay->isOpenAt(Time::fromString('18:00')));
    }
}
