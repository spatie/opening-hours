<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Exceptions\InvalidTimeString;
use Spatie\OpeningHours\Time;

class TimeTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_a_string()
    {
        $this->assertSame('00:00', (string) Time::fromString('00:00'));
        $this->assertSame('16:32', (string) Time::fromString('16:32'));
        $this->assertSame('24:00', (string) Time::fromString('24:00'));
    }

    #[Test]
    public function it_cant_be_created_from_an_invalid_string()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('aa:bb');
    }

    #[Test]
    public function it_cant_be_created_from_an_invalid_hour()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('26:00');
    }

    #[Test]
    public function it_cant_be_created_from_an_out_of_bound_hour()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('24:01');
    }

    #[Test]
    public function it_cant_be_created_from_an_invalid_minute()
    {
        $this->expectException(InvalidTimeString::class);

        Time::fromString('14:60');
    }

    #[Test]
    public function it_can_be_created_from_a_date_time_instance()
    {
        $dateTime = new DateTime('2016-09-27 16:00:00');

        $this->assertSame('16:00', (string) Time::fromDateTime($dateTime));

        $dateTime = new DateTimeImmutable('2016-09-27 16:00:00');

        $this->assertSame('16:00', (string) Time::fromDateTime($dateTime));
    }

    #[Test]
    public function it_can_determine_that_its_the_same_as_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isSame(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isSame(Time::fromString('10:00')));
        $this->assertFalse(Time::fromString('09:00')->isSame(Time::fromString('09:30')));
    }

    #[Test]
    public function it_ignores_metadata_when_comparing_times()
    {
        $timeWithDataA = Time::fromString('09:00', data: ['label' => 'morning']);
        $timeWithDataB = Time::fromString('09:00', data: ['label' => 'opening']);

        $this->assertTrue($timeWithDataA->isSame($timeWithDataB));
        $this->assertFalse($timeWithDataA->isBefore($timeWithDataB));
        $this->assertFalse($timeWithDataA->isAfter($timeWithDataB));
        $this->assertTrue($timeWithDataA->isSameOrAfter($timeWithDataB));
        $this->assertTrue($timeWithDataA->isSameOrBefore($timeWithDataB));
    }

    #[Test]
    public function it_ignores_date_context_when_comparing_times()
    {
        $timeWithDateA = Time::fromString('09:00', date: new DateTimeImmutable('2020-01-01'));
        $timeWithDateB = Time::fromString('09:00', date: new DateTimeImmutable('2025-06-15'));

        $this->assertTrue($timeWithDateA->isSame($timeWithDateB));
        $this->assertFalse($timeWithDateA->isBefore($timeWithDateB));
        $this->assertFalse($timeWithDateA->isAfter($timeWithDateB));
        $this->assertTrue($timeWithDateA->isSameOrAfter($timeWithDateB));
        $this->assertTrue($timeWithDateA->isSameOrBefore($timeWithDateB));
    }

    #[Test]
    public function it_compares_end_of_day_midnight_correctly()
    {
        $endOfDay = Time::fromString('24:00');
        $lastMinute = Time::fromString('23:59');
        $midnight = Time::fromString('00:00');

        $this->assertTrue($endOfDay->isAfter($lastMinute));
        $this->assertFalse($endOfDay->isSame($midnight));
        $this->assertFalse($endOfDay->isBefore($midnight));
        $this->assertTrue($endOfDay->isAfter($midnight));
        $this->assertFalse($lastMinute->isAfter($endOfDay));
    }

    #[Test]
    public function it_can_determine_that_its_before_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isBefore(Time::fromString('10:00')));
        $this->assertTrue(Time::fromString('09:00')->isBefore(Time::fromString('09:30')));
        $this->assertFalse(Time::fromString('09:00')->isBefore(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isBefore(Time::fromString('08:00')));
        $this->assertFalse(Time::fromString('09:00')->isBefore(Time::fromString('08:30')));
        $this->assertFalse(Time::fromString('08:30')->isBefore(Time::fromString('08:00')));
    }

    #[Test]
    public function it_can_determine_that_its_after_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isAfter(Time::fromString('08:00')));
        $this->assertTrue(Time::fromString('09:30')->isAfter(Time::fromString('09:00')));
        $this->assertTrue(Time::fromString('09:00')->isAfter(Time::fromString('08:30')));
        $this->assertFalse(Time::fromString('09:00')->isAfter(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isAfter(Time::fromString('09:30')));
        $this->assertFalse(Time::fromString('09:00')->isAfter(Time::fromString('10:00')));
    }

    #[Test]
    public function it_can_determine_that_its_the_same_or_after_another_time()
    {
        $this->assertTrue(Time::fromString('09:00')->isSameOrAfter(Time::fromString('08:00')));
        $this->assertTrue(Time::fromString('09:00')->isSameOrAfter(Time::fromString('09:00')));
        $this->assertTrue(Time::fromString('09:30')->isSameOrAfter(Time::fromString('09:30')));
        $this->assertTrue(Time::fromString('09:30')->isSameOrAfter(Time::fromString('09:00')));
        $this->assertFalse(Time::fromString('09:00')->isSameOrAfter(Time::fromString('10:00')));
    }

    #[Test]
    public function it_can_accept_any_date_format_with_the_date_time_interface()
    {
        $dateTime = date_create_immutable('2012-11-06 13:25:59.123456');

        $this->assertSame('13:25', (string) Time::fromDateTime($dateTime));
    }

    #[Test]
    public function it_can_be_formatted()
    {
        $this->assertSame('09:00', Time::fromString('09:00')->format());
        $this->assertSame('09:00', Time::fromString('09:00')->format('H:i'));
        $this->assertSame('9 AM', Time::fromString('09:00')->format('g A'));
    }

    #[Test]
    public function it_can_get_hours_and_minutes()
    {
        $time = Time::fromString('16:30');
        $this->assertSame(16, $time->hours());
        $this->assertSame(30, $time->minutes());
    }

    #[Test]
    public function it_can_calculate_diff()
    {
        $time1 = Time::fromString('16:30');
        $time2 = Time::fromString('16:05');
        $this->assertSame(0, $time1->diff($time2)->h);
        $this->assertSame(25, $time1->diff($time2)->i);
    }

    #[Test]
    public function it_should_not_mutate_passed_datetime()
    {
        $dateTime = new DateTime('2016-09-27 12:00:00');
        $time = Time::fromString('15:00');
        $this->assertSame('2016-09-27 15:00:00', $time->toDateTime($dateTime)->format('Y-m-d H:i:s'));
        $this->assertSame('2016-09-27 12:00:00', $dateTime->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_should_not_mutate_passed_datetime_immutable()
    {
        $dateTime = new DateTimeImmutable('2016-09-27 12:00:00');
        $time = Time::fromString('15:00');
        $this->assertSame('2016-09-27 15:00:00', $time->toDateTime($dateTime)->format('Y-m-d H:i:s'));
        $this->assertSame('2016-09-27 12:00:00', $dateTime->format('Y-m-d H:i:s'));
    }
}
