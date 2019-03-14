<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeImmutable;
use Spatie\OpeningHours\Time;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Exceptions\InvalidTimeString;

class TimeTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_a_string()
    {
        $this->assertEquals('16:00', (string) Time::fromString('16:00'));
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_string()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('aa:bb');
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_hour()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('26:00');
    }

    /** @test */
    public function it_cant_be_created_from_an_out_of_bound_hour()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('24:01');
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_minute()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('14:60');
    }

    /** @test */
    public function it_can_be_created_from_a_date_time_instance()
    {
        $dateTime = new DateTime('2016-09-27 16:00:00');

        $this->assertEquals('16:00', (string) Time::fromDateTime($dateTime));

        $dateTime = new DateTimeImmutable('2016-09-27 16:00:00');

        $this->assertEquals('16:00', (string) Time::fromDateTime($dateTime));
    }

    /** @test */
    public function it_can_determine_that_its_the_same_as_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isSame(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isSame(Time::fromString('10:00')));
        $this->assertFalse(Time::fromString('09:00')->isSame(Time::fromString('09:30')));
    }

    /** @test */
    public function it_can_determine_that_its_before_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isBefore(Time::fromString('10:00')));
        $this->assertTrue(Time::fromString('09:00')->isBefore(Time::fromString('09:30')));
        $this->assertFalse(Time::fromString('09:00')->isBefore(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isBefore(Time::fromString('08:00')));
        $this->assertFalse(Time::fromString('09:00')->isBefore(Time::fromString('08:30')));
        $this->assertFalse(Time::fromString('08:30')->isBefore(Time::fromString('08:00')));
    }

    /** @test */
    public function it_can_determine_that_its_after_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isAfter(Time::fromString('08:00')));
        $this->assertTrue(Time::fromString('09:30')->isAfter(Time::fromString('09:00')));
        $this->assertTrue(Time::fromString('09:00')->isAfter(Time::fromString('08:30')));
        $this->assertFalse(Time::fromString('09:00')->isAfter(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isAfter(Time::fromString('09:30')));
        $this->assertFalse(Time::fromString('09:00')->isAfter(Time::fromString('10:00')));
    }

    /** @test */
    public function it_can_determine_that_its_the_same_or_after_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isSameOrAfter(Time::fromString('08:00')));
        $this->assertTrue(Time::fromString('09:00')->isSameOrAfter(Time::fromString('09:00')));
        $this->assertTrue(Time::fromString('09:30')->isSameOrAfter(Time::fromString('09:30')));
        $this->assertTrue(Time::fromString('09:30')->isSameOrAfter(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isSameOrAfter(Time::fromString('10:00')));
    }

    /** @test */
    public function it_can_accept_any_date_format_with_the_date_time_interface()
    {
        $dateTime = date_create_immutable('2012-11-06 13:25:59.123456');

        $this->assertEquals('13:25', (string) Time::fromDateTime($dateTime));
    }

    /** @test */
    public function it_can_be_formatted()
    {
        $this->assertEquals('09:00', Time::fromString('09:00')->format());
        $this->assertEquals('09:00', Time::fromString('09:00')->format('H:i'));
        $this->assertEquals('9 AM', Time::fromString('09:00')->format('g A'));
    }

    /** @test */
    public function it_can_get_hours_and_minutes()
    {
        $time = Time::fromString('16:30');
        $this->assertEquals(16, $time->hours());
        $this->assertEquals(30, $time->minutes());
    }

    /** @test */
    public function it_can_calculate_diff()
    {
        $time1 = Time::fromString('16:30');
        $time2 = Time::fromString('16:05');
        $this->assertEquals(0, $time1->diff($time2)->h);
        $this->assertEquals(25, $time1->diff($time2)->i);
    }

    /** @test */
    public function it_should_not_mutate_passed_datetime()
    {
        $dateTime = new DateTime('2016-09-27 12:00:00');
        $time = Time::fromString('15:00');
        $this->assertEquals('2016-09-27 15:00:00', $time->toDateTime($dateTime)->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-09-27 12:00:00', $dateTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_not_mutate_passed_datetime_immutable()
    {
        $dateTime = new DateTimeImmutable('2016-09-27 12:00:00');
        $time = Time::fromString('15:00');
        $this->assertEquals('2016-09-27 15:00:00', $time->toDateTime($dateTime)->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-09-27 12:00:00', $dateTime->format('Y-m-d H:i:s'));
    }
}
