<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\TimeRange;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursOverflowTest extends TestCase
{
    /** @test */
    public function it_fills_opening_hours_with_overflow()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
        ], null);

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('monday')[0]);
        $this->assertEquals((string) $openingHours->forDay('monday')[0], '09:00-02:00');
    }

    /** @test */
    public function check_open_with_overflow()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
            'tuesday' => ['19:00-04:00'],
            'wednesday' => ['09:00-02:00'],
        ], null);

        $shouldBeOpen = new DateTime('2019-04-23 01:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-23 03:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-23 18:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-23 20:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-23 23:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-24 02:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-24 03:59:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-24 04:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeOpen));

        $shouldBeOpen = new DateTime('2019-04-24 09:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
    }

    /** @test */
    public function check_open_with_overflow_immutable()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
        ], null);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
    }

    /** @test */
    public function next_close_with_overflow()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
        ], null);

        $shouldBeOpen = new DateTime('2019-04-23 01:00:00');
        $this->assertEquals('2019-04-23 02:00:00', $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function next_close_with_overflow_immutable()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
        ], null);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertEquals('2019-04-23 02:00:00', $nextTimeClosed);
        $this->assertEquals('2019-04-23 01:00:00', $shouldBeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function overflow_on_simple_ranges()
    {
        //Tuesday 4th of June 2019, 11.35 am
        $time = new DateTime('2019-06-04 11:35:00');

        $openWithOverflow = OpeningHours::create([
            'overflow' => true,
            'monday' => ['11:00-18:00'],
            'tuesday' => ['13:37-15:37'],
        ]);

        $openWithoutOverflow = OpeningHours::create([
            'overflow' => false,
            'monday' => ['11:00-18:00'],
            'tuesday' => ['13:37-15:37'],
        ]);

        $this->assertFalse($openWithOverflow->isOpenAt($time));
        $this->assertFalse($openWithoutOverflow->isOpenAt($time));
    }
}
