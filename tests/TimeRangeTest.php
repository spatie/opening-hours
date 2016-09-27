<?php

namespace Spatie\OpeningHours\Test;

use PHPUnit_Framework_TestCase;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

class TimeRangeTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created_from_a_string()
    {
        $this->assertEquals('16:00-18:00', (string) TimeRange::fromString('16:00-18:00'));
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_range()
    {
        $this->expectException(InvalidTimeRangeString::class);

        TimeRange::fromString('16:00/18:00');
    }

    /** @test */
    public function it_can_determine_that_it_spills_over_to_the_next_day()
    {
        $this->assertTrue(TimeRange::fromString('18:00-01:00')->spillsOverToNextDay());
        $this->assertFalse(TimeRange::fromString('18:00-23:00')->spillsOverToNextDay());
    }

    /** @test */
    public function it_can_determine_that_it_contains_a_time()
    {
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('16:00')));
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('17:00')));
        $this->assertFalse(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('18:00')));

        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('00:30')));
        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('22:00')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('17:00')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('02:00')));
    }
}
