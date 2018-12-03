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

    /** @test */
    public function it_should_merge_ranges_on_explicitly_create_from_overlapping_ranges()
    {
        $hours = OpeningHours::createAndMergeOverlappingRanges([
            'monday' => [
                '08:00-12:00',
                '11:30-13:30',
                '13:00-18:00',
            ],
            'tuesday' => [
                '08:00-12:00',
                '11:30-13:30',
                '15:00-18:00',
                '16:00-17:00',
                '19:00-20:00',
                '20:00-21:00',
            ],
        ]);
        $dump = [];
        foreach (['monday', 'tuesday'] as $day) {
            $dump[$day] = [];
            foreach ($hours->forDay($day) as $range) {
                $dump[$day][] = $range->format();
            }
        }

        $this->assertSame([
            '08:00-18:00',
        ], $dump['monday']);
        $this->assertSame([
            '08:00-13:30',
            '15:00-18:00',
            '19:00-21:00',
        ], $dump['tuesday']);
    }
}
