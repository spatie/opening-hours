<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Day;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\TimeRange;

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
        $this->assertSame((string) $openingHours->forDay('monday')[0], '09:00-18:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('tuesday')[0]);
        $this->assertSame((string) $openingHours->forDay('tuesday')[0], '09:00-18:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('wednesday')[0]);
        $this->assertSame((string) $openingHours->forDay('wednesday')[0], '09:00-12:00');

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('wednesday')[1]);
        $this->assertSame((string) $openingHours->forDay('wednesday')[1], '14:00-18:00');

        $this->assertCount(0, $openingHours->forDay('thursday'));

        $this->assertInstanceOf(TimeRange::class, $openingHours->forDay('friday')[0]);
        $this->assertSame((string) $openingHours->forDay('friday')[0], '09:00-20:00');

        $this->assertCount(0, $openingHours->forDate(new DateTime('2016-09-26 11:00:00')));
        $this->assertCount(0, $openingHours->forDate(new DateTimeImmutable('2016-09-26 11:00:00')));
    }

    /** @test */
    public function it_can_map_week_with_a_callback()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'tuesday' => ['10:00-18:00'],
            'wednesday' => ['09:00-12:00', '14:00-18:00'],
            'thursday' => [],
            'friday' => ['14:00-20:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $this->assertSame([
            'monday' => 9,
            'tuesday' => 10,
            'wednesday' => 9,
            'thursday' => null,
            'friday' => 14,
            'saturday' => null,
            'sunday' => null,
        ], $openingHours->map(function (OpeningHoursForDay $ranges) {
            return $ranges->isEmpty() ? null : $ranges->offsetGet(0)->start()->hours();
        }));
    }

    /** @test */
    public function it_can_map_exceptions_with_a_callback()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'tuesday' => ['10:00-18:00'],
            'wednesday' => ['09:00-12:00', '14:00-18:00'],
            'thursday' => [],
            'friday' => ['14:00-20:00'],
            'exceptions' => [
                '2016-09-26' => [],
                '10-10' => ['14:00-20:00'],
            ],
        ]);

        $this->assertSame([
            '2016-09-26' => null,
            '10-10' => 14,
        ], $openingHours->mapExceptions(function (OpeningHoursForDay $ranges) {
            return $ranges->isEmpty() ? null : $ranges->offsetGet(0)->start()->hours();
        }));
    }

    /** @test */
    public function it_can_handle_empty_input()
    {
        $openingHours = OpeningHours::create([]);

        foreach (Day::cases() as $day) {
            $this->assertCount(0, $openingHours->forDay($day));
        }
    }

    /** @test */
    public function it_handles_day_names_in_a_case_insensitive_manner()
    {
        $openingHours = OpeningHours::create([
            'Monday' => ['09:00-18:00'],
        ]);

        $this->assertSame((string) $openingHours->forDay('monday')[0], '09:00-18:00');

        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $this->assertSame((string) $openingHours->forDay('Monday')[0], '09:00-18:00');
    }

    /** @test */
    public function it_will_throw_an_exception_when_using_an_invalid_day_name()
    {
        $this->expectExceptionObject(InvalidDayName::invalidDayName('mmmmonday'));

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

        $this->assertSame('Newyearsday opening times', $hours->exceptions()['2011-01-01']->data);
        $this->assertSame('Newyearsday opening times', $hours->forDate(new DateTime('2011-01-01'))->data);
        $this->assertSame('Newyearsday next day', $hours->exceptions()['2011-01-02']->data);
        $this->assertSame('Christmas', $hours->exceptions()['12-25']->data);
        $this->assertSame('Christmas', $hours->forDate(new DateTime('2011-12-25'))->data);
        $this->assertNull($hours->forDay('monday')->data);
        $this->assertSame('foobar', $hours->forDay('tuesday')->data);
        $this->assertSame(2, $hours->forDay('tuesday')->count());
        $this->assertSame(['foobar'], $hours->forDay('wednesday')->data);
        $this->assertSame(1, $hours->forDay('wednesday')->count());
        $this->assertSame(['foobar'], $hours->forDay('thursday')[0]->data);
        $this->assertNull($hours->forDay('thursday')[1]->data);

        $hours = OpeningHours::create([
            'monday' => [
                ['09:00-12:00', 'morning'],
                ['13:00-18:00', 'afternoon'],
            ],
        ]);

        $this->assertSame('morning', $hours->forDay('monday')[0]->data);
        $this->assertSame('afternoon', $hours->forDay('monday')[1]->data);

        $hours = OpeningHours::create([
            'tuesday' => [
                '09:00-12:00',
                '13:00-18:00',
                [
                    '19:00-21:00',
                    'data' => 'Extra on Tuesday evening',
                ],
            ],
        ]);

        $this->assertSame('09:00-12:00,13:00-18:00,19:00-21:00', strval($hours->forDay('tuesday')));
        $this->assertNull($hours->forDay('tuesday')[1]->data);
        $this->assertSame('Extra on Tuesday evening', $hours->forDay('tuesday')[2]->data);
    }

    /** @test */
    public function it_handle_filters()
    {
        $typicalDay = [
            '08:00-12:00',
            '14:00-18:00',
        ];
        $hours = OpeningHours::create([
            'monday' => $typicalDay,
            'tuesday' => $typicalDay,
            'wednesday' => $typicalDay,
            'thursday' => $typicalDay,
            'friday' => $typicalDay,
            'exceptions' => [
                // Closure in exceptions will be handled as a filter.
                function (DateTimeImmutable $date) {
                    if ($date->format('Y-m-d') === $date->modify('first monday of this month')->format('Y-m-d')) {
                        // Big lunch each first monday of the month
                        return [
                            '08:00-11:00',
                            '15:00-18:00',
                        ];
                    }
                },
            ],
            'filters' => [
                function (DateTimeImmutable $date) {
                    $year = intval($date->format('Y'));
                    $easterMonday = new DateTimeImmutable('2018-03-21 +'.(easter_days($year) + 1).'days');
                    if ($date->format('m-d') === $easterMonday->format('m-d')) {
                        return []; // Closed on Easter monday
                    }
                },
                function (DateTimeImmutable $date) use ($typicalDay) {
                    if ($date->format('m') === $date->format('d')) {
                        return [
                            'hours' => $typicalDay,
                            'data' => 'Month equals day',
                        ];
                    }
                },
            ],
        ]);

        $this->assertCount(3, $hours->getFilters());
        $this->assertSame('08:00-11:00,15:00-18:00', $hours->forDate(new DateTimeImmutable('2018-12-03'))->__toString());
        $this->assertSame('08:00-12:00,14:00-18:00', $hours->forDate(new DateTimeImmutable('2018-12-10'))->__toString());
        $this->assertSame('', $hours->forDate(new DateTimeImmutable('2018-04-02'))->__toString());
        $this->assertSame('04-03 08:00', $hours->nextOpen(new DateTimeImmutable('2018-03-31'))->format('m-d H:i'));
        $this->assertSame('12-03 11:00', $hours->nextClose(new DateTimeImmutable('2018-12-03'))->format('m-d H:i'));
        $this->assertSame('Month equals day', $hours->forDate(new DateTimeImmutable('2018-12-12'))->data);
    }

    /** @test */
    public function it_should_merge_ranges_on_explicitly_create_from_overlapping_ranges()
    {
        $hours = OpeningHours::createAndMergeOverlappingRanges([
            'monday' => [
                '08:00-12:00',
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

    /** @test */
    public function it_should_merge_ranges_including_explicit_24_00()
    {
        $hours = OpeningHours::createAndMergeOverlappingRanges([
            'monday' => [
                '08:00-12:00',
                '12:00-24:00',
            ],
        ]);
        $dump = [];
        foreach ($hours->forDay('monday') as $range) {
            $dump[] = $range->format();
        }

        $this->assertSame([
            '08:00-24:00',
        ], $dump);
    }

    /** @test */
    public function it_should_reorder_ranges()
    {
        $hours = OpeningHours::createAndMergeOverlappingRanges([
            'monday' => [
                '13:00-24:00',
                '08:00-12:00',
            ],
        ]);

        $this->assertSame('08:00', $hours->nextOpen(new DateTime('2019-07-06 07:25'))->format('H:i'));
    }
}
