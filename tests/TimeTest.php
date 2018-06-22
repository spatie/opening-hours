<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Exceptions\InvalidTimeString;
use Spatie\OpeningHours\Time;

class TimeTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_a_string()
    {
        $this->assertEquals('16:00', (string)Time::fromString('16:00'));
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_string()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('aa:bb');
    }

    /** @test */
    public function it_can_be_created_from_a_date_time_instance()
    {
        $dateTime = new DateTime('2016-09-27 16:00:00');

        $this->assertEquals('16:00', (string)Time::fromDateTime($dateTime));
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

        $this->assertEquals('13:25', (string)Time::fromDateTime($dateTime));
    }

    /** @test */
    public function it_can_be_formatted()
    {
        $this->assertEquals('09:00', Time::fromString('09:00')->format());
        $this->assertEquals('09:00', Time::fromString('09:00')->format('H:i'));
        $this->assertEquals('9 AM', Time::fromString('09:00')->format('g A'));
    }
}
