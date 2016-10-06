<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use Spatie\OpeningHours\Day;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
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
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('monday')[0]);
        $this->assertEquals((string) $openingHours->forDay('monday')[0], '09:00-18:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('tuesday')[0]);
        $this->assertEquals((string) $openingHours->forDay('tuesday')[0], '09:00-18:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('wednesday')[0]);
        $this->assertEquals((string) $openingHours->forDay('wednesday')[0], '09:00-12:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('wednesday')[1]);
        $this->assertEquals((string) $openingHours->forDay('wednesday')[1], '14:00-18:00');

        $this->assertCount(0, $openingHours->forDay('thursday'));

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('friday')[0]);
        $this->assertEquals((string) $openingHours->forDay('friday')[0], '09:00-20:00');

        $this->assertCount(0, $openingHours->forDate(new DateTime('2016-09-26 11:00:00')));
    }

    /** @test */
    public function it_can_handle_empty_input()
    {
        $openingHours = OpeningHours::create([]);

        foreach (Day::days() as $dayName) {
            $this->assertCount(0, $openingHours->forDay($dayName));
        }
    }

    /** @test */
    public function it_handles_day_names_in_a_case_insensitive_manner()
    {
        $openingHours = OpeningHours::create([
            'Monday' => ['09:00-18:00'],
        ]);

        $this->assertEquals((string) $openingHours->forDay('monday')[0], '09:00-18:00');

        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $this->assertEquals((string) $openingHours->forDay('Monday')[0], '09:00-18:00');
    }

    /** @test */
    public function it_will_throw_an_exception_when_using_an_invalid_day_name()
    {
        $this->expectException(InvalidDayName::class);

        OpeningHours::create(['mmmmonday' => ['09:00-18:00']]);
    }

    /** @test */
    public function it_will_throw_an_exception_when_using_an_invalid_exception_date()
    {
        $this->expectException(InvalidDate::class);

        OpeningHours::create([
            'exceptions' => [
                '25/12/2016' => []
            ]
        ]);
    }
}
