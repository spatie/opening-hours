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
            'monday' => ['09:00-02:00'],
        ], null, true);

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('monday')[0]);
        $this->assertEquals((string) $openingHours->forDay('monday')[0], '09:00-02:00');
    }

    /** @test */
    public function check_open_with_overflow()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-02:00'],
        ], null, true);

        $shouldBeOpen = new DateTime('2019-04-23 01:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
    }

    /** @test */
    public function check_open_with_overflow_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-02:00'],
        ], null, true);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
    }

    /** @test */
    public function next_close_with_overflow()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-02:00'],
        ], null, true);

        $shouldBeOpen = new DateTime('2019-04-23 01:00:00');
        $this->assertEquals('2019-04-23 02:00:00', $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function next_close_with_overflow_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-02:00'],
        ], null, true);

        $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
        $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
        $this->assertEquals('2019-04-23 02:00:00', $nextTimeClosed);
        $this->assertEquals('2019-04-23 01:00:00', $shouldBeOpen->format('Y-m-d H:i:s'));
    }
}
