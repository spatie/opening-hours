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
    public function it_store_meta_data()
    {
        $hours = OpeningHours::create([
            'monday' => [
                '09:00-12:00',
                '13:00-18:00',
            ],
            'tuesday' => [
                '09:00-12:00',
                '13:00-18:00',
                'data' => 'foobar',
            ],
            'wednesday' => [
                'hours' => ['09:00-12:00'],
                'data' => ['foobar'],
            ],
            'thursday' => [
                [
                    'hours' => '09:00-12:00',
                    'data' => ['foobar'],
                ],
                '13:00-18:00',
            ],
            'exceptions' => [
                '2011-01-01' => [
                    'hours' => ['13:00-18:00'],
                    'data' => 'Newyearsday opening times',
                ],
                '2011-01-02' => [
                    '13:00-18:00',
                    'data' => 'Newyearsday next day',
                ],
                '12-25' => [
                    'data' => 'Christmas',
                ],
            ],
        ]);

        $this->assertSame('Newyearsday opening times', $hours->exceptions()['2011-01-01']->getData());
        $this->assertSame('Newyearsday opening times', $hours->forDate(new DateTime('2011-01-01'))->getData());
        $this->assertSame('Newyearsday next day', $hours->exceptions()['2011-01-02']->getData());
        $this->assertSame('Christmas', $hours->exceptions()['12-25']->getData());
        $this->assertSame('Christmas', $hours->forDate(new DateTime('2011-12-25'))->getData());
        $this->assertNull($hours->forDay('monday')->getData());
        $this->assertSame('foobar', $hours->forDay('tuesday')->getData());
        $this->assertSame(2, $hours->forDay('tuesday')->count());
        $this->assertSame(['foobar'], $hours->forDay('wednesday')->getData());
        $this->assertSame(1, $hours->forDay('wednesday')->count());
        $this->assertSame(['foobar'], $hours->forDay('thursday')[0]->getData());
        $this->assertNull($hours->forDay('thursday')[1]->getData());
    }
}
