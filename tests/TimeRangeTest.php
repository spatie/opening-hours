<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeArray;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeList;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

class TimeRangeTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_a_string()
    {
        $this->assertSame('16:00-18:00', (string) TimeRange::fromString('16:00-18:00'));
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_range()
    {
        $this->expectException(InvalidTimeRangeString::class);

        TimeRange::fromString('16:00/18:00');
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_a_invalid_array()
    {
        $this->expectException(InvalidTimeRangeArray::class);

        TimeRange::fromArray([]);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_a_empty_array_to_list()
    {
        $this->expectException(InvalidTimeRangeList::class);

        TimeRange::fromList([]);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_a_invalid_array_to_list()
    {
        $this->expectException(InvalidTimeRangeList::class);

        TimeRange::fromList([
            'foo',
        ]);
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
        $this->assertFalse(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('18:00')));

        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('00:30')));
        $this->assertTrue(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('00:30')));
        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('22:00')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('17:00')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('02:00')));
        $this->assertFalse(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('02:00')));

        $this->assertTrue(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('18:00')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('00:59')));
        $this->assertFalse(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('01:00')));
        $this->assertTrue(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('00:59')));
        $this->assertFalse(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('01:00')));
    }

    /** @test */
    public function it_can_determine_that_it_contains_a_time_over_midnight()
    {
        $this->assertFalse(TimeRange::fromString('10:00-18:00')->containsNightTime(Time::fromString('17:00')));
        $this->assertFalse(TimeRange::fromString('18:00-10:00')->containsNightTime(Time::fromString('17:00')));
        $this->assertFalse(TimeRange::fromString('10:00-18:00')->containsNightTime(Time::fromString('08:00')));
        $this->assertTrue(TimeRange::fromString('18:00-10:00')->containsNightTime(Time::fromString('08:00')));
    }

    /** @test */
    public function it_can_determine_that_it_overlaps_another_time_range()
    {
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('15:00-17:00')));
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('17:00-19:00')));
        $this->assertTrue(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('17:00-17:30')));

        $this->assertTrue(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('21:00-23:00')));
        $this->assertFalse(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('01:00-02:00')));
        $this->assertTrue(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('23:00-23:30')));

        $this->assertFalse(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('14:00-15:00')));
        $this->assertFalse(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('19:00-20:00')));
    }

    /** @test */
    public function it_can_be_formatted()
    {
        $this->assertSame('16:00-18:00', TimeRange::fromString('16:00-18:00')->format());
        $this->assertSame('16:00 - 18:00', TimeRange::fromString('16:00-18:00')->format('H:i', '%s - %s'));
        $this->assertSame('from 4 PM to 6 PM', TimeRange::fromString('16:00-18:00')->format('g A', 'from %s to %s'));
    }

    /** @test */
    public function it_can_return_dates_relative_to_a_given_one()
    {
        $date = new DateTime('2020-09-20 17:23:45');
        $start = TimeRange::fromString('16:00-18:00')->startOn($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-20 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endOn($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-20 18:00:00', $end->format('Y-m-d H:i:s'));
        $start = TimeRange::fromString('16:00-18:00')->startBefore($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-20 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endBefore($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-19 18:00:00', $end->format('Y-m-d H:i:s'));
        $start = TimeRange::fromString('16:00-18:00')->startAfter($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-21 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endAfter($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-20 18:00:00', $end->format('Y-m-d H:i:s'));

        $date = new DateTime('2020-09-20 15:23:45');
        $start = TimeRange::fromString('16:00-18:00')->startOn($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-20 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endOn($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-20 18:00:00', $end->format('Y-m-d H:i:s'));
        $start = TimeRange::fromString('16:00-18:00')->startBefore($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-19 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endBefore($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-19 18:00:00', $end->format('Y-m-d H:i:s'));
        $start = TimeRange::fromString('16:00-18:00')->startAfter($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-20 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endAfter($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-20 18:00:00', $end->format('Y-m-d H:i:s'));

        $date = new DateTime('2020-09-20 21:23:45');
        $start = TimeRange::fromString('16:00-18:00')->startOn($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-20 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endOn($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-20 18:00:00', $end->format('Y-m-d H:i:s'));
        $start = TimeRange::fromString('16:00-18:00')->startBefore($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-20 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endBefore($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-20 18:00:00', $end->format('Y-m-d H:i:s'));
        $start = TimeRange::fromString('16:00-18:00')->startAfter($date);
        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertSame('2020-09-21 16:00:00', $start->format('Y-m-d H:i:s'));
        $end = TimeRange::fromString('16:00-18:00')->endAfter($date);
        $this->assertInstanceOf(DateTime::class, $end);
        $this->assertSame('2020-09-21 18:00:00', $end->format('Y-m-d H:i:s'));
    }
}
