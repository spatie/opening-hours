<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use Spatie\OpeningHours\Day;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\TimeRange;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;

class OpeningHoursFillTest extends TestCase
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

    /**
     * @test
     */
    public function it_can_parse_osm_strings() {
        $openingHours = OpeningHours::fromOsmString('Mo-Fr 08:00-12:00,13:30-17:30; Aug 01 off');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('monday')[0]);
        $this->assertEquals((string) $openingHours->forDay('monday')[0], '08:00-12:00');
        $this->assertEquals((string) $openingHours->forDay('monday')[1], '13:30-17:30');
        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('tuesday')[0]);
        $this->assertEquals((string) $openingHours->forDay('tuesday')[0], '08:00-12:00');
        $this->assertEquals((string) $openingHours->forDay('tuesday')[1], '13:30-17:30');
        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('wednesday')[0]);
        $this->assertEquals((string) $openingHours->forDay('wednesday')[0], '08:00-12:00');
        $this->assertEquals((string) $openingHours->forDay('wednesday')[1], '13:30-17:30');
        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('thursday')[0]);
        $this->assertEquals((string) $openingHours->forDay('thursday')[0], '08:00-12:00');
        $this->assertEquals((string) $openingHours->forDay('thursday')[1], '13:30-17:30');
        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('friday')[0]);
        $this->assertEquals((string) $openingHours->forDay('friday')[0], '08:00-12:00');
        $this->assertEquals((string) $openingHours->forDay('friday')[1], '13:30-17:30');

//        var_dump($openingHours->exceptions(), $openingHours->forDate(new DateTime('2018-08-01 11:00:00')));
        $this->assertCount(0, $openingHours->forDate(new DateTime('2018-08-01 11:00:00')));
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
                '25/12/2016' => [],
            ],
        ]);
    }
}
