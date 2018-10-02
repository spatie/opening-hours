<?php

namespace Spatie\OpeningHours\Test;

use Spatie\OpeningHours\Time;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\TimeRange;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;

class TimeRangeTest extends TestCase
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
    public function it_can_get_the_time_objects()
    {
        $timeRange = TimeRange::fromString('16:00-18:00');

        $this->assertInstanceOf(Time::class, $timeRange->start());
        $this->assertInstanceOf(Time::class, $timeRange->end());
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
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('18:00')));
        $this->assertFalse(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('18:01')));

        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('00:30')));
        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('22:00')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('17:00')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('02:00')));

        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('18:00')));
        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('01:00')));
    }

    /** @test */
    public function it_can_determine_that_it_overlaps_another_time_range()
    {
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('15:00-17:00')));
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('17:00-19:00')));
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('17:00-17:30')));

        $this->assertTrue(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('21:00-23:00')));
        $this->assertTrue(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('01:00-02:00')));
        $this->assertTrue(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('23:00-23:30')));

        $this->assertFalse(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('14:00-15:00')));
        $this->assertFalse(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('19:00-20:00')));
    }

    /** @test */
    public function it_can_be_formatted()
    {
        $this->assertEquals('16:00-18:00', TimeRange::fromString('16:00-18:00')->format());
        $this->assertEquals('16:00 - 18:00', TimeRange::fromString('16:00-18:00')->format('H:i', '%s - %s'));
        $this->assertEquals('from 4 PM to 6 PM', TimeRange::fromString('16:00-18:00')->format('g A', 'from %s to %s'));
    }
}
