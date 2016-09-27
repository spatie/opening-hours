<?php

namespace Spatie\OpeningHours\Test;

use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\TimeRange;

class OpeningHoursFillTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_fills_opening_hours()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'tuesday' => ['09:00-18:00'],
            'wednesday' => ['09:00-12:00', '14:00-18:00'],
            'thursday' => [],
            'friday' => ['09:00-20:00'],
        ]);

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('monday')[0]);
        $this->assertEquals((string) $openingHours->forDay('monday')[0], '09:00-18:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('tuesday')[0]);
        $this->assertEquals((string) $openingHours->forDay('tuesday')[0], '09:00-18:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('wednesday')[0]);
        $this->assertEquals((string) $openingHours->forDay('wednesday')[0], '09:00-12:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('wednesday')[1]);
        $this->assertEquals((string) $openingHours->forDay('wednesday')[1], '14:00-18:00');

        $this->assertEmpty($openingHours->forDay('thursday'));

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('friday')[0]);
        $this->assertEquals((string) $openingHours->forDay('friday')[0], '09:00-20:00');
    }

    public function it_fills_exceptions()
    {

    }
}
