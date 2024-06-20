<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\TimeRange;

class OpeningHoursOverflowTest extends TestCase
{
    #[Test]
    public function it_fills_opening_hours_with_overflow()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
        ], null);

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('monday')[0]);
        $this->assertSame((string) $openingHours->forDay('monday')[0], '09:00-02:00');
    }

    #[Test]
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

    #[Test]
    public function check_open_with_overflow_immutable()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
        ], null);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
    }

    #[Test]
    public function next_close_with_overflow()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'],
        ], null);

        $shouldBeOpen = new DateTime('2019-04-23 01:00:00');
        $this->assertSame('2019-04-23 02:00:00', $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function next_close_with_overflow_immutable()
    {
        $openingHours = OpeningHours::create([
            'overflow' => true,
            'monday' => ['09:00-02:00'], // 2019-04-22
        ], null);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-23 02:00:00', $nextTimeClosed);

        $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:00:00');
        $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-23 02:00:00', $nextTimeClosed);

        $shouldBeOpen = new DateTimeImmutable('2019-04-22 07:00:00');
        $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-23 02:00:00', $nextTimeClosed);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $nextTimeClosed = $openingHours->nextOpen($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-29 09:00:00', $nextTimeClosed);

        $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:00:00');
        $nextTimeClosed = $openingHours->nextOpen($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-29 09:00:00', $nextTimeClosed);

        $shouldBeOpen = new DateTimeImmutable('2019-04-22 07:00:00');
        $nextTimeClosed = $openingHours->nextOpen($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-22 09:00:00', $nextTimeClosed);

        $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:30:00');
        $previousTimeOpen = $openingHours->previousOpen($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-22 09:00:00', $previousTimeOpen);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $previousTimeOpen = $openingHours->previousOpen($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-22 09:00:00', $previousTimeOpen);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 05:00:00');
        $previousTimeOpen = $openingHours->previousOpen($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-22 09:00:00', $previousTimeOpen);

        $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:30:00');
        $previousTimeOpen = $openingHours->previousClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-22 02:00:00', $previousTimeOpen);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $previousTimeOpen = $openingHours->previousClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-22 02:00:00', $previousTimeOpen);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 05:00:00');
        $previousTimeOpen = $openingHours->previousClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertSame('2019-04-22 02:00:00', $previousTimeOpen);
    }

    #[Test]
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
