<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Exceptions\MaximumLimitExceeded;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

class OpeningHoursTest extends TestCase
{
    /** @test */
    public function it_can_return_the_opening_hours_for_a_regular_week()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $openingHoursForWeek = $openingHours->forWeek();

        $this->assertCount(7, $openingHoursForWeek);
        $this->assertSame('09:00-18:00', (string) $openingHoursForWeek['monday'][0]);
        $this->assertCount(0, $openingHoursForWeek['tuesday']);
        $this->assertCount(0, $openingHoursForWeek['wednesday']);
        $this->assertCount(0, $openingHoursForWeek['thursday']);
        $this->assertCount(0, $openingHoursForWeek['friday']);
        $this->assertCount(0, $openingHoursForWeek['saturday']);
        $this->assertCount(0, $openingHoursForWeek['sunday']);
    }

    /** @test */
    public function it_can_return_consecutive_opening_hours_for_a_regular_week()
    {
        $openingHours = OpeningHours::create([
            'monday'    => [],
            'tuesday'   => ['09:00-18:00'],
            'wednesday' => ['09:00-18:00'],
            'thursday'  => ['09:00-18:00'],
            'friday'    => ['09:00-20:00'],
            'saturday'  => ['09:00-17:00'],
            'sunday'    => [],
        ]);

        $openingHoursForWeek = $openingHours->forWeekConsecutiveDays();

        $this->assertCount(5, $openingHoursForWeek);
        $this->assertInstanceOf(OpeningHoursForDay::class, $openingHoursForWeek['tuesday']['opening_hours']);
        $this->assertSame('09:00-18:00', (string) $openingHoursForWeek['tuesday']['opening_hours']);
        $this->assertSame('wednesday', array_values($openingHoursForWeek['tuesday']['days'])[1]);

        $openingHours = OpeningHours::create([
            'monday'    => [],
            'tuesday'   => ['09:00-18:00'],
            'wednesday' => ['09:00-15:00'],
            'thursday'  => ['09:00-18:00'],
            'friday'    => ['09:00-18:00'],
            'saturday'  => ['09:00-15:00'],
            'sunday'    => [],
        ]);

        $dump = array_map(function ($data) {
            return implode(', ', $data['days']).': '.((string) $data['opening_hours']);
        }, $openingHours->forWeekConsecutiveDays());

        $this->assertSame([
            'monday'    => 'monday: ',
            'tuesday'   => 'tuesday: 09:00-18:00',
            'wednesday' => 'wednesday: 09:00-15:00',
            'thursday'  => 'thursday, friday: 09:00-18:00',
            'saturday'  => 'saturday: 09:00-15:00',
            'sunday'    => 'sunday: ',
        ], $dump);

        $openingHours = OpeningHours::create([
            'tuesday'   => ['09:00-18:00'],
            'wednesday' => ['09:00-15:00'],
            'thursday'  => ['09:00-18:00'],
            'friday'    => ['09:00-18:00'],
            'saturday'  => ['09:00-15:00'],
            'sunday'    => [],
            'monday'    => [],
        ]);

        $dump = array_map(function ($data) {
            return implode(', ', $data['days']).': '.((string) $data['opening_hours']);
        }, $openingHours->forWeekConsecutiveDays());

        $this->assertSame([
            'monday'    => 'monday: ',
            'tuesday'   => 'tuesday: 09:00-18:00',
            'wednesday' => 'wednesday: 09:00-15:00',
            'thursday'  => 'thursday, friday: 09:00-18:00',
            'saturday'  => 'saturday: 09:00-15:00',
            'sunday'    => 'sunday: ',
        ], $dump);
    }

    /** @test */
    public function it_can_return_combined_opening_hours_for_a_regular_week()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['09:00-18:00'],
            'tuesday'   => ['09:00-18:00'],
            'wednesday' => ['11:00-15:00'],
            'thursday'  => ['11:00-15:00'],
            'friday'    => ['12:00-14:00'],
        ]);

        $openingHoursForWeek = $openingHours->forWeekCombined();

        $this->assertCount(4, $openingHoursForWeek);
        $this->assertInstanceOf(OpeningHoursForDay::class, $openingHoursForWeek['wednesday']['opening_hours']);
        $this->assertSame('11:00-15:00', (string) $openingHoursForWeek['wednesday']['opening_hours']);
        $this->assertSame('thursday', array_values($openingHoursForWeek['wednesday']['days'])[1]);

        $openingHours = OpeningHours::create([
            'monday'    => [],
            'tuesday'   => ['09:00-18:00'],
            'wednesday' => ['09:00-15:00'],
            'thursday'  => ['09:00-18:00'],
            'friday'    => ['09:00-18:00'],
            'saturday'  => ['09:00-15:00'],
            'sunday'    => [],
        ]);

        $dump = array_map(function ($data) {
            return implode(', ', $data['days']).': '.((string) $data['opening_hours']);
        }, $openingHours->forWeekCombined());

        $this->assertSame([
            'monday'    => 'monday, sunday: ',
            'tuesday'   => 'tuesday, thursday, friday: 09:00-18:00',
            'wednesday' => 'wednesday, saturday: 09:00-15:00',
        ], $dump);
    }

    /** @test */
    public function it_can_validate_the_opening_hours()
    {
        $valid = OpeningHours::isValid([
            'monday' => ['09:00-18:00'],
        ]);

        $invalid = OpeningHours::isValid([
            'notaday' => ['18:00-09:00'],
        ]);

        $this->assertTrue($valid);
        $this->assertFalse($invalid);
    }

    /** @test */
    public function it_can_return_the_exceptions()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-18:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $exceptions = $openingHours->exceptions();

        $this->assertCount(1, $exceptions);
        $this->assertCount(0, $exceptions['2016-09-26']);
    }

    /** @test */
    public function it_can_return_the_opening_hours_for_a_regular_week_day()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $openingHoursForMonday = $openingHours->forDay('monday');
        $this->assertCount(1, $openingHoursForMonday);
        $this->assertInstanceOf(TimeRange::class, $openingHoursForMonday[0]);
        $this->assertSame('09:00-18:00', (string) $openingHoursForMonday[0]);

        $openingHoursForTuesday = $openingHours->forDay('tuesday');
        $this->assertCount(0, $openingHoursForTuesday);
    }

    /** @test */
    public function it_can_determine_that_its_regularly_open_on_a_week_day()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $this->assertTrue($openingHours->isOpenOn('monday'));
        $this->assertFalse($openingHours->isOpenOn('tuesday'));
        $this->assertFalse($openingHours->isOpenOn('2019-08-31'));
        $this->assertFalse($openingHours->isOpenOn('2019-09-01'));
        $this->assertTrue($openingHours->isOpenOn('2020-08-31'));
        $this->assertFalse($openingHours->isOpenOn('2020-09-01'));
        $this->assertTrue($openingHours->isOpenOn((new DateTime('First Monday of January'))->format('m-d')));
        $this->assertFalse($openingHours->isOpenOn((new DateTime('First Tuesday of January'))->format('m-d')));
    }

    /** @test */
    public function it_can_determine_that_its_regularly_closed_on_a_week_day()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $this->assertFalse($openingHours->isClosedOn('monday'));
        $this->assertTrue($openingHours->isClosedOn('tuesday'));
        $this->assertTrue($openingHours->isClosedOn('2019-08-31'));
        $this->assertTrue($openingHours->isClosedOn('2019-09-01'));
        $this->assertFalse($openingHours->isClosedOn('2020-08-31'));
        $this->assertTrue($openingHours->isClosedOn('2020-09-01'));
        $this->assertFalse($openingHours->isClosedOn((new DateTime('First Monday of January'))->format('m-d')));
        $this->assertTrue($openingHours->isClosedOn((new DateTime('First Tuesday of January'))->format('m-d')));
    }

    /** @test */
    public function it_can_return_the_opening_hours_for_a_specific_date()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-18:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $openingHoursForMonday1909 = $openingHours->forDate(new DateTime('2016-09-19 00:00:00'));
        $openingHoursForMonday2609 = $openingHours->forDate(new DateTime('2016-09-26 00:00:00'));

        $this->assertCount(1, $openingHoursForMonday1909);
        $this->assertInstanceOf(TimeRange::class, $openingHoursForMonday1909[0]);
        $this->assertSame('09:00-18:00', (string) $openingHoursForMonday1909[0]);

        $this->assertCount(0, $openingHoursForMonday2609);

        $openingHoursForMonday1909 = $openingHours->forDate(new DateTimeImmutable('2016-09-19 00:00:00'));
        $openingHoursForMonday2609 = $openingHours->forDate(new DateTimeImmutable('2016-09-26 00:00:00'));

        $this->assertCount(1, $openingHoursForMonday1909);
        $this->assertInstanceOf(TimeRange::class, $openingHoursForMonday1909[0]);
        $this->assertSame('09:00-18:00', (string) $openingHoursForMonday1909[0]);

        $this->assertCount(0, $openingHoursForMonday2609);
    }

    /** @test */
    public function it_can_determine_that_its_open_at_a_certain_date_and_time()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $shouldBeOpen = new DateTime('2016-09-26 11:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
        $this->assertFalse($openingHours->isClosedAt($shouldBeOpen));

        $shouldBeOpen = new DateTimeImmutable('2016-09-26 11:00:00');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
        $this->assertFalse($openingHours->isClosedAt($shouldBeOpen));

        $shouldBeOpenAlternativeDate = date_create_immutable('2016-09-26 11:12:13.123456');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpenAlternativeDate));
        $this->assertFalse($openingHours->isClosedAt($shouldBeOpenAlternativeDate));

        $shouldBeClosedBecauseOfTime = new DateTime('2016-09-26 20:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosedBecauseOfTime));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosedBecauseOfTime));

        $shouldBeClosedBecauseOfTime = new DateTimeImmutable('2016-09-26 20:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosedBecauseOfTime));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosedBecauseOfTime));

        $shouldBeClosedBecauseOfDay = new DateTime('2016-09-27 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosedBecauseOfDay));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosedBecauseOfDay));

        $shouldBeClosedBecauseOfDay = new DateTimeImmutable('2016-09-27 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosedBecauseOfDay));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosedBecauseOfDay));
    }

    /** @test */
    public function it_can_determine_that_its_open_at_a_certain_date_and_time_on_an_exceptional_day()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-18:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $shouldBeClosed = new DateTime('2016-09-26 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosed));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosed));

        $shouldBeClosed = new DateTimeImmutable('2016-09-26 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosed));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosed));
    }

    /** @test */
    public function it_can_determine_that_its_open_at_a_certain_date_and_time_on_an_recurring_exceptional_day()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-18:00'],
            'exceptions' => [
                '01-01' => [],
                '12-25' => ['09:00-12:00'],
                '12-26' => [],
            ],
        ]);

        $closedOnNewYearDay = new DateTime('2017-01-01 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnNewYearDay));
        $this->assertTrue($openingHours->isClosedAt($closedOnNewYearDay));

        $closedOnNewYearDay = new DateTimeImmutable('2017-01-01 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnNewYearDay));
        $this->assertTrue($openingHours->isClosedAt($closedOnNewYearDay));

        $closedOnSecondChristmasDay = new DateTime('2025-12-16 12:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnSecondChristmasDay));
        $this->assertTrue($openingHours->isClosedAt($closedOnSecondChristmasDay));

        $closedOnSecondChristmasDay = new DateTimeImmutable('2025-12-16 12:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnSecondChristmasDay));
        $this->assertTrue($openingHours->isClosedAt($closedOnSecondChristmasDay));

        $openOnChristmasMorning = new DateTime('2025-12-25 10:00:00');
        $this->assertTrue($openingHours->isOpenAt($openOnChristmasMorning));
        $this->assertFalse($openingHours->isClosedAt($openOnChristmasMorning));

        $openOnChristmasMorning = new DateTimeImmutable('2025-12-25 10:00:00');
        $this->assertTrue($openingHours->isOpenAt($openOnChristmasMorning));
        $this->assertFalse($openingHours->isClosedAt($openOnChristmasMorning));
    }

    /** @test */
    public function it_can_prioritize_exceptions_by_giving_full_dates_priority()
    {
        $openingHours = OpeningHours::create([
            'exceptions' => [
                '2018-01-01' => ['09:00-18:00'],
                '01-01'      => [],
                '12-25'      => ['09:00-12:00'],
                '12-26'      => [],
            ],
        ]);

        $openOnNewYearDay2018 = new DateTime('2018-01-01 11:00:00');
        $this->assertTrue($openingHours->isOpenAt($openOnNewYearDay2018));
        $this->assertFalse($openingHours->isClosedAt($openOnNewYearDay2018));

        $openOnNewYearDay2018 = new DateTimeImmutable('2018-01-01 11:00:00');
        $this->assertTrue($openingHours->isOpenAt($openOnNewYearDay2018));
        $this->assertFalse($openingHours->isClosedAt($openOnNewYearDay2018));

        $closedOnNewYearDay2019 = new DateTime('2019-01-01 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnNewYearDay2019));
        $this->assertTrue($openingHours->isClosedAt($closedOnNewYearDay2019));

        $closedOnNewYearDay2019 = new DateTimeImmutable('2019-01-01 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnNewYearDay2019));
        $this->assertTrue($openingHours->isClosedAt($closedOnNewYearDay2019));
    }

    /** @test */
    public function it_can_handle_consecutive_open_hours()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['09:00-24:00'],
            'tuesday'   => ['00:00-24:00'],
            'wednesday' => ['00:00-03:00', '09:00-24:00'],
            'friday'    => ['00:00-03:00'],
        ]);

        $monday = new DateTime('2019-02-04 11:00:00');
        $dayHours = $openingHours->forDay('monday');
        $this->assertTrue($openingHours->isOpenAt($monday));
        $this->assertFalse($openingHours->isClosedAt($monday));
        $this->assertSame('09:00-24:00', $dayHours->nextOpenRange(Time::fromString('08:00'))->format());
        //$this->assertFalse($dayHours->previousOpenRange(Time::fromString('08:00')));
        $this->assertFalse($dayHours->nextOpenRange(Time::fromString('10:00')));
        $this->assertFalse($dayHours->previousOpenRange(Time::fromString('10:00')));
        $this->assertSame('09:00-24:00', $dayHours->nextCloseRange(Time::fromString('08:00'))->format());
        $this->assertFalse($dayHours->previousCloseRange(Time::fromString('10:00')));
        $this->assertSame('09:00-24:00', $dayHours->nextCloseRange(Time::fromString('10:00'))->format());
        $this->assertSame('2019-02-06 03:00:00', $openingHours->nextClose($monday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-06 09:00:00', $openingHours->nextOpen($monday)->format('Y-m-d H:i:s'));

        $this->assertSame('2019-02-01 03:00:00', $openingHours->previousClose($monday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-04 09:00:00', $openingHours->previousOpen($monday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-01 00:00:00', $openingHours->previousOpen(new DateTime('2019-02-04 08:50:00'))->format('Y-m-d H:i:s'));

        $monday = new DateTimeImmutable('2019-02-04 11:00:00');
        $this->assertTrue($openingHours->isOpenAt($monday));
        $this->assertFalse($openingHours->isClosedAt($monday));
        $this->assertSame('2019-02-06 03:00:00', $openingHours->nextClose($monday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-06 09:00:00', $openingHours->nextOpen($monday)->format('Y-m-d H:i:s'));

        $wednesday = new DateTime('2019-02-06 09:00:00');
        $dayHours = $openingHours->forDay('wednesday');
        $this->assertTrue($openingHours->isOpenAt($wednesday));
        $this->assertFalse($openingHours->isClosedAt($wednesday));
        $this->assertSame('00:00-03:00', $dayHours->previousCloseRange(Time::fromString('08:00'))->format());
        $this->assertSame('00:00-03:00', $dayHours->previousCloseRange(Time::fromString('08:00'))->format());
        $this->assertSame('00:00', $dayHours->previousOpen(Time::fromString('08:00'))->format());
        $this->assertSame('03:00', $dayHours->previousClose(Time::fromString('08:00'))->format());
        $this->assertSame('2019-02-07 00:00:00', $openingHours->nextClose($wednesday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-08 00:00:00', $openingHours->nextOpen($wednesday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-06 03:00:00', $openingHours->previousClose($wednesday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-04 09:00:00', $openingHours->previousOpen($wednesday)->format('Y-m-d H:i:s'));

        $wednesday = new DateTimeImmutable('2019-02-06 09:00:00');
        $this->assertTrue($openingHours->isOpenAt($wednesday));
        $this->assertFalse($openingHours->isClosedAt($wednesday));
        $this->assertSame('2019-02-07 00:00:00', $openingHours->nextClose($wednesday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-08 00:00:00', $openingHours->nextOpen($wednesday)->format('Y-m-d H:i:s'));

        $friday = new DateTimeImmutable('2019-02-08 09:00:00');
        $this->assertSame('2019-02-08 03:00:00', $openingHours->previousClose($friday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-08 00:00:00', $openingHours->previousOpen($friday)->format('Y-m-d H:i:s'));

        $friday = new DateTimeImmutable('2019-02-08 02:00:00');
        $this->assertSame('2019-02-07 00:00:00', $openingHours->previousClose($friday)->format('Y-m-d H:i:s'));
        $this->assertSame('2019-02-08 00:00:00', $openingHours->previousOpen($friday)->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_non_working_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-11:00', '13:00-19:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-26 09:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_edges_time()
    {
        $openingHours = OpeningHours::create([
            'monday'  => ['09:00-11:00', '13:00-19:00'], // 2016-09-26
            'tuesday' => ['09:00-11:00', '13:00-19:00'], // 2016-09-27
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 00:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 00:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-20 13:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 09:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 09:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-20 13:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 11:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 11:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-26 09:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-26 09:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 13:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-27 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 13:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-26 09:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 19:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-27 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 19:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 23:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-27 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 23:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-27 23:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-27 13:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));

        $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-27 08:00:00'));

        $this->assertInstanceOf(DateTime::class, $previousTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $previousTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_mixed_structures()
    {
        $openingHours = OpeningHours::create([
            'monday' => [
                [
                    'hours' => '09:00-11:00',
                    'data'  => ['foobar'],
                ],
                '13:00-19:00',
            ],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 00:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-11 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 00:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-11 11:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 09:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-11 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 09:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-11 11:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 10:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-11 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 10:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-11 11:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 11:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-11 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 11:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-11 19:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-11 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-11 19:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 13:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-18 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 13:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-11 19:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 15:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-18 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 15:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-11 19:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 19:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-18 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 19:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-18 11:00:00', $nextTimeClose->format('Y-m-d H:i:s'));

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 21:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2019-02-18 09:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 21:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2019-02-18 11:00:00', $nextTimeClose->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_non_working_date_time_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-11:00', '13:00-19:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));

        /** @var CustomDate $nextTimeOpen */
        $nextTimeOpen = $openingHours->nextOpen(new CustomDate('2016-09-26 12:00:00'));

        $this->assertInstanceOf(CustomDate::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 13:00:00', $nextTimeOpen->foo());
    }

    /** @test */
    public function it_can_determine_next_close_hours_from_non_working_date_time()
    {
        $ranges = [
            'monday'     => ['09:00-18:00'],
            /* all the default week settings */
            'exceptions' => [
                // add non-dynamic exceptions, else let empty
            ],
        ];
        $dynamicClosedRanges = [
            '2016-11-07' => ['12:30-13:00'],
        ];
        foreach ($dynamicClosedRanges as $day => $closedRanges) {
            $weekDay = strtolower((new DateTime($day))->format('l'));
            $dayRanges = \Spatie\OpeningHours\OpeningHoursForDay::fromStrings($ranges[$weekDay]);
            $newRanges = [];

            foreach ($dayRanges as $dayRange) {
                /* @var \Spatie\OpeningHours\TimeRange $dayRange */
                foreach ($closedRanges as $exceptionRange) {
                    $range = \Spatie\OpeningHours\TimeRange::fromString($exceptionRange);
                    if ($dayRange->containsTime($range->start()) && $dayRange->containsTime($range->end())) {
                        $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($dayRange->start()->format().'-'.$range->start()->format())->format();
                        $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($range->end()->format().'-'.$dayRange->end()->format())->format();
                        continue 2;
                    }
                    if ($dayRange->containsTime($range->start())) {
                        $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($dayRange->start()->format().'-'.$range->start()->format())->format();
                        continue 2;
                    }
                    if ($dayRange->containsTime($range->end())) {
                        $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($range->end()->format().'-'.$dayRange->end()->format())->format();
                        continue 2;
                    }
                }

                $newRanges[] = $dayRange->format();
            }

            $ranges['exceptions'][$day] = $newRanges;
        }

        $openingHours = OpeningHours::createAndMergeOverlappingRanges($ranges);

        $this->assertSame('09:00-12:30,13:00-18:00', strval($openingHours->forDate(new DateTime('2016-11-07'))));
        $this->assertSame('09:00-18:00', strval($openingHours->forDate(new DateTime('2016-11-14'))));
    }

    /** @test */
    public function it_can_determine_next_close_hours_from_non_working_date_time_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-11:00', '13:00-19:00'],
        ]);

        $nextTimeOpen = $openingHours->nextClose(new DateTimeImmutable('2016-09-26 12:00:00'));

        $this->assertInstanceOf(DateTimeImmutable::class, $nextTimeOpen);
        $this->assertSame('2016-09-26 19:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_working_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday'  => ['09:00-11:00', '13:00-19:00'],
            'tuesday' => ['10:00-11:00', '14:00-19:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 16:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-27 10:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_working_date_time_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday'  => ['09:00-11:00', '13:00-19:00'],
            'tuesday' => ['10:00-11:00', '14:00-19:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTimeImmutable('2016-09-26 16:00:00'));

        $this->assertInstanceOf(DateTimeImmutable::class, $nextTimeOpen);
        $this->assertSame('2016-09-27 10:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_close_hours_from_working_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday'  => ['09:00-11:00', '13:00-19:00'],
            'tuesday' => ['10:00-11:00', '14:00-19:00'],
        ]);

        $nextTimeClose = $openingHours->nextClose(new DateTime('2016-09-26 16:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeClose);
        $this->assertSame('2016-09-26 19:00:00', $nextTimeClose->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_close_hours_from_working_date_time_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday'  => ['09:00-11:00', '13:00-19:00'],
            'tuesday' => ['10:00-11:00', '14:00-19:00'],
        ]);

        $nextTimeClose = $openingHours->nextClose(new DateTimeImmutable('2016-09-26 16:00:00'));

        $this->assertInstanceOf(DateTimeImmutable::class, $nextTimeClose);
        $this->assertSame('2016-09-26 19:00:00', $nextTimeClose->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_early_morning()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-11:00', '13:00-19:00'],
            'tuesday'    => ['10:00-11:00', '14:00-19:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 04:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertSame('2016-09-27 10:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_early_morning_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-11:00', '13:00-19:00'],
            'tuesday'    => ['10:00-11:00', '14:00-19:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTimeImmutable('2016-09-26 04:00:00'));

        $this->assertInstanceOf(DateTimeImmutable::class, $nextTimeOpen);
        $this->assertSame('2016-09-27 10:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_close_hours_from_early_morning()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-11:00', '13:00-19:00'],
            'tuesday'    => ['10:00-11:00', '14:00-19:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $nextClosedTime = $openingHours->nextClose(new DateTime('2016-09-26 04:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextClosedTime);
        $this->assertSame('2016-09-27 11:00:00', $nextClosedTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_close_hours_from_early_morning_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['09:00-11:00', '13:00-19:00'],
            'tuesday'    => ['10:00-11:00', '14:00-19:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $nextClosedTime = $openingHours->nextClose(new DateTimeImmutable('2016-09-26 04:00:00'));

        $this->assertInstanceOf(DateTimeImmutable::class, $nextClosedTime);
        $this->assertSame('2016-09-27 11:00:00', $nextClosedTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_set_the_timezone_on_the_openings_hours_object()
    {
        $openingHours = new OpeningHours('Europe/Amsterdam');
        $openingHours->fill([
            'monday'     => ['09:00-18:00'],
            'exceptions' => [
                '2016-11-14' => ['09:00-13:00'],
            ],
        ]);

        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 10:00')));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 15:59')));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 08:00')));
        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 06:00')));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 06:00', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:00', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 17:59', new DateTimeZone('Europe/Amsterdam'))));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-11-14 17:59', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-11-14 12:59', new DateTimeZone('Europe/Amsterdam'))));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-11-14 15:59', new DateTimeZone('America/Denver'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:59', new DateTimeZone('America/Denver'))));

        $this->assertTrue($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 10:00')));
        $this->assertTrue($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 15:59')));
        $this->assertTrue($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 08:00')));
        $this->assertFalse($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 06:00')));

        $this->assertFalse($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 06:00', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 09:00', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 17:59', new DateTimeZone('Europe/Amsterdam'))));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-11-14 17:59', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-11-14 12:59', new DateTimeZone('Europe/Amsterdam'))));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-11-14 15:59', new DateTimeZone('America/Denver'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:59', new DateTimeZone('America/Denver'))));

        date_default_timezone_set('America/Denver');
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:59')));
        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 10:00')));

        $this->assertTrue($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 09:59')));
        $this->assertFalse($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 10:00')));
    }

    /** @test */
    public function it_can_determine_that_its_open_now()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['00:00-23:59'],
            'tuesday'   => ['00:00-23:59'],
            'wednesday' => ['00:00-23:59'],
            'thursday'  => ['00:00-23:59'],
            'friday'    => ['00:00-23:59'],
            'saturday'  => ['00:00-23:59'],
            'sunday'    => ['00:00-23:59'],
        ]);

        $this->assertTrue($openingHours->isOpen());
    }

    /** @test */
    public function it_can_determine_that_its_closed_now()
    {
        $openingHours = OpeningHours::create([]);

        $this->assertTrue($openingHours->isClosed());
    }

    /** @test */
    public function it_can_retrieve_regular_closing_days_as_strings()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['09:00-18:00'],
            'tuesday'   => ['09:00-18:00'],
            'wednesday' => ['09:00-18:00'],
            'thursday'  => ['09:00-18:00'],
            'friday'    => ['09:00-18:00'],
            'saturday'  => [],
            'sunday'    => [],
        ]);

        $this->assertSame(['saturday', 'sunday'], $openingHours->regularClosingDays());
    }

    /** @test */
    public function it_can_retrieve_regular_closing_days_as_iso_numbers()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['09:00-18:00'],
            'tuesday'   => ['09:00-18:00'],
            'wednesday' => ['09:00-18:00'],
            'thursday'  => ['09:00-18:00'],
            'friday'    => ['09:00-18:00'],
            'saturday'  => [],
            'sunday'    => [],
        ]);

        $this->assertSame([6, 7], $openingHours->regularClosingDaysISO());
    }

    /** @test */
    public function it_can_retrieve_a_list_of_exceptional_closing_dates()
    {
        $openingHours = OpeningHours::create([
            'exceptions' => [
                '2017-06-01' => [],
                '2017-06-02' => [],
            ],
        ]);

        $exceptionalClosingDates = $openingHours->exceptionalClosingDates();

        $this->assertCount(2, $exceptionalClosingDates);
        $this->assertSame('2017-06-01', $exceptionalClosingDates[0]->format('Y-m-d'));
        $this->assertSame('2017-06-02', $exceptionalClosingDates[1]->format('Y-m-d'));
    }

    /** @test */
    public function it_works_when_starting_at_midnight()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['00:00-16:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime());
        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
    }

    /** @test */
    public function it_works_when_starting_at_midnight_immutable()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['00:00-16:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTimeImmutable());
        $this->assertInstanceOf(DateTimeImmutable::class, $nextTimeOpen);
    }

    /** @test */
    public function it_can_set_the_timezone()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['00:00-16:00'],
        ]);
        $openingHours->setTimezone('Asia/Taipei');
        $openingHoursForWeek = $openingHours->forWeek();

        $this->assertCount(7, $openingHoursForWeek);
        $this->assertSame('00:00-16:00', (string) $openingHoursForWeek['monday'][0]);
        $this->assertCount(0, $openingHoursForWeek['tuesday']);
        $this->assertCount(0, $openingHoursForWeek['wednesday']);
        $this->assertCount(0, $openingHoursForWeek['thursday']);
        $this->assertCount(0, $openingHoursForWeek['friday']);
        $this->assertCount(0, $openingHoursForWeek['saturday']);
        $this->assertCount(0, $openingHoursForWeek['sunday']);
    }

    /** @test */
    public function it_can_set_the_timezone_on_construct_with_date_time_zone()
    {
        $openingHours = new OpeningHours(new DateTimeZone('Asia/Taipei'));
        $openingHours->fill([
            'monday' => ['00:00-16:00'],
        ]);
        $openingHoursForWeek = $openingHours->forWeek();

        $this->assertCount(7, $openingHoursForWeek);
        $this->assertSame('00:00-16:00', (string) $openingHoursForWeek['monday'][0]);
        $this->assertCount(0, $openingHoursForWeek['tuesday']);
        $this->assertCount(0, $openingHoursForWeek['wednesday']);
        $this->assertCount(0, $openingHoursForWeek['thursday']);
        $this->assertCount(0, $openingHoursForWeek['friday']);
        $this->assertCount(0, $openingHoursForWeek['saturday']);
        $this->assertCount(0, $openingHoursForWeek['sunday']);
    }

    /** @test */
    public function it_can_set_the_timezone_on_construct_with_string()
    {
        $openingHours = new OpeningHours('Asia/Taipei');
        $openingHours->fill([
            'monday' => ['00:00-16:00'],
        ]);
        $openingHoursForWeek = $openingHours->forWeek();

        $this->assertCount(7, $openingHoursForWeek);
        $this->assertSame('00:00-16:00', (string) $openingHoursForWeek['monday'][0]);
        $this->assertCount(0, $openingHoursForWeek['tuesday']);
        $this->assertCount(0, $openingHoursForWeek['wednesday']);
        $this->assertCount(0, $openingHoursForWeek['thursday']);
        $this->assertCount(0, $openingHoursForWeek['friday']);
        $this->assertCount(0, $openingHoursForWeek['saturday']);
        $this->assertCount(0, $openingHoursForWeek['sunday']);
    }

    /** @test */
    public function it_throw_an_exception_on_invalid_timezone()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Timezone');

        new OpeningHours(['foo']);
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_void_array_next_open()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No open date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([])->nextOpen(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_void_array_previous_open()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No open date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([])->previousOpen(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_full_array_next_open()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No open date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([
            'monday'    => ['00:00-24:00'],
            'tuesday'   => ['00:00-24:00'],
            'wednesday' => ['00:00-24:00'],
            'thursday'  => ['00:00-24:00'],
            'friday'    => ['00:00-24:00'],
            'saturday'  => ['00:00-24:00'],
            'sunday'    => ['00:00-24:00'],
        ])->nextOpen(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_full_array_previous_open()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No open date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([
            'monday'    => ['00:00-24:00'],
            'tuesday'   => ['00:00-24:00'],
            'wednesday' => ['00:00-24:00'],
            'thursday'  => ['00:00-24:00'],
            'friday'    => ['00:00-24:00'],
            'saturday'  => ['00:00-24:00'],
            'sunday'    => ['00:00-24:00'],
        ])->previousOpen(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_full_array_next_open_with_exceptions()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No open date/time found in the next 366 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([
            'monday'     => ['00:00-24:00'],
            'tuesday'    => ['00:00-24:00'],
            'wednesday'  => ['00:00-24:00'],
            'thursday'   => ['00:00-24:00'],
            'friday'     => ['00:00-24:00'],
            'saturday'   => ['00:00-24:00'],
            'sunday'     => ['00:00-24:00'],
            'exceptions' => [
                '2022-09-05' => [],
            ],
        ])->nextOpen(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_void_array_next_close()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No close date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([])->nextClose(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_void_array_previous_close()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No close date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([])->previousClose(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_full_array_next_close()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No close date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([
            'monday'    => ['00:00-24:00'],
            'tuesday'   => ['00:00-24:00'],
            'wednesday' => ['00:00-24:00'],
            'thursday'  => ['00:00-24:00'],
            'friday'    => ['00:00-24:00'],
            'saturday'  => ['00:00-24:00'],
            'sunday'    => ['00:00-24:00'],
        ])->nextClose(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_throw_an_exception_on_limit_exceeded_full_array_previous_close()
    {
        $this->expectException(MaximumLimitExceeded::class);
        $this->expectExceptionMessage('No close date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

        OpeningHours::create([
            'monday'    => ['00:00-24:00'],
            'tuesday'   => ['00:00-24:00'],
            'wednesday' => ['00:00-24:00'],
            'thursday'  => ['00:00-24:00'],
            'friday'    => ['00:00-24:00'],
            'saturday'  => ['00:00-24:00'],
            'sunday'    => ['00:00-24:00'],
        ])->previousClose(new DateTime('2019-06-06 19:02:00'));
    }

    /** @test */
    public function it_should_handle_far_exception()
    {
        $this->assertSame('2019-12-25 00:00:00', OpeningHours::create([
            'monday'     => ['00:00-24:00'],
            'tuesday'    => ['00:00-24:00'],
            'wednesday'  => ['00:00-24:00'],
            'thursday'   => ['00:00-24:00'],
            'friday'     => ['00:00-24:00'],
            'saturday'   => ['00:00-24:00'],
            'sunday'     => ['00:00-24:00'],
            'exceptions' => [
                '12-25' => [],
            ],
        ])->nextClose(new DateTime('2019-06-06 19:02:00'))->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_handle_very_far_future_exception_by_changing_limit()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['00:00-24:00'],
            'tuesday'    => ['00:00-24:00'],
            'wednesday'  => ['00:00-24:00'],
            'thursday'   => ['00:00-24:00'],
            'friday'     => ['00:00-24:00'],
            'saturday'   => ['00:00-24:00'],
            'sunday'     => ['00:00-24:00'],
            'exceptions' => [
                '2022-12-25' => [],
            ],
        ]);
        $openingHours->setDayLimit(3000);

        $this->assertSame('2022-12-25 00:00:00', $openingHours->nextClose(new DateTime('2019-06-06 19:02:00'))->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_handle_very_far_past_exception_by_changing_limit()
    {
        $openingHours = OpeningHours::create([
            'monday'     => ['00:00-24:00'],
            'tuesday'    => ['00:00-24:00'],
            'wednesday'  => ['00:00-24:00'],
            'thursday'   => ['00:00-24:00'],
            'friday'     => ['00:00-24:00'],
            'saturday'   => ['00:00-24:00'],
            'sunday'     => ['00:00-24:00'],
            'exceptions' => [
                '2013-12-25' => [],
            ],
        ]);
        $openingHours->setDayLimit(3000);

        $this->assertSame('2013-12-26 00:00:00', $openingHours->previousOpen(new DateTime('2019-06-06 19:02:00'))->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_handle_open_range()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['10:00-16:00', '19:30-20:30'],
            'tuesday'   => ['22:30-04:00'],
            'wednesday' => ['07:00-10:00'],
            'thursday'  => ['09:00-12:00'],
            'friday'    => ['09:00-12:00'],
            'saturday'  => [],
            'sunday'    => [],
        ]);

        $this->assertFalse($openingHours->currentOpenRange(new DateTime('2019-07-15 08:00:00')));
        $this->assertFalse($openingHours->currentOpenRange(new DateTime('2019-07-15 17:00:00')));
        $this->assertFalse($openingHours->currentOpenRange(new DateTime('2019-07-16 22:00:00')));
        $this->assertFalse($openingHours->currentOpenRange(new DateTime('2019-07-17 04:00:00')));
        $this->assertSame('10:00-16:00', $openingHours->currentOpenRange(new DateTime('2019-07-15 11:00:00'))->format());
        $this->assertSame('19:30-20:30', $openingHours->currentOpenRange(new DateTime('2019-07-15 20:00:00'))->format());
        $this->assertSame('22:30-04:00', $openingHours->currentOpenRange(new DateTime('2019-07-16 22:30:00'))->format());
        $this->assertSame('22:30-04:00', $openingHours->currentOpenRange(new DateTime('2019-07-16 22:40:00'))->format());
        $this->assertSame('22:30-04:00', $openingHours->currentOpenRange(new DateTime('2019-07-17 03:59:59'))->format());
        $this->assertSame('07:00-10:00', $openingHours->currentOpenRange(new DateTime('2019-07-17 07:59:59'))->format());
    }

    /** @test */
    public function it_should_handle_open_start_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['10:00-16:00', '19:30-20:30'],
            'tuesday'   => ['22:30-04:00'],
            'wednesday' => ['07:00-10:00'],
            'thursday'  => ['09:00-12:00'],
            'friday'    => ['09:00-12:00'],
            'saturday'  => [],
            'sunday'    => [],
        ]);

        $this->assertFalse($openingHours->currentOpenRangeStart(new DateTime('2019-07-15 08:00:00')));
        $this->assertFalse($openingHours->currentOpenRangeStart(new DateTime('2019-07-15 17:00:00')));
        $this->assertFalse($openingHours->currentOpenRangeStart(new DateTime('2019-07-16 22:00:00')));
        $this->assertFalse($openingHours->currentOpenRangeStart(new DateTime('2019-07-17 04:00:00')));
        $this->assertSame('2019-07-15 10:00:00', $openingHours->currentOpenRangeStart(new DateTime('2019-07-15 11:00:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-15 19:30:00', $openingHours->currentOpenRangeStart(new DateTime('2019-07-15 20:00:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-16 22:30:00', $openingHours->currentOpenRangeStart(new DateTime('2019-07-16 22:30:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-16 22:30:00', $openingHours->currentOpenRangeStart(new DateTime('2019-07-16 22:40:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-16 22:30:00', $openingHours->currentOpenRangeStart(new DateTime('2019-07-17 03:59:59'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-17 07:00:00', $openingHours->currentOpenRangeStart(new DateTime('2019-07-17 07:59:59'))->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_handle_open_end_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['10:00-16:00', '19:30-20:30'],
            'tuesday'   => ['22:30-04:00'],
            'wednesday' => ['07:00-10:00'],
            'thursday'  => ['09:00-12:00'],
            'friday'    => ['09:00-12:00'],
            'saturday'  => [],
            'sunday'    => [],
        ]);

        $this->assertFalse($openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 08:00:00')));
        $this->assertFalse($openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 17:00:00')));
        $this->assertFalse($openingHours->currentOpenRangeEnd(new DateTime('2019-07-16 22:00:00')));
        $this->assertFalse($openingHours->currentOpenRangeEnd(new DateTime('2019-07-17 04:00:00')));
        $this->assertSame('2019-07-15 16:00:00', $openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 11:00:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-15 20:30:00', $openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 20:00:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-17 04:00:00', $openingHours->currentOpenRangeEnd(new DateTime('2019-07-16 22:30:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-17 04:00:00', $openingHours->currentOpenRangeEnd(new DateTime('2019-07-16 22:40:00'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-17 04:00:00', $openingHours->currentOpenRangeEnd(new DateTime('2019-07-17 03:59:59'))->format('Y-m-d H:i:s'));
        $this->assertSame('2019-07-17 10:00:00', $openingHours->currentOpenRangeEnd(new DateTime('2019-07-17 07:59:59'))->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_support_empty_arrays_with_merge()
    {
        $hours = OpeningHours::createAndMergeOverlappingRanges(
            [
                'exceptions' => [
                    '01-01' => [
                        'hours' => [],
                        'data'  => [
                            'id' => 'my_id',
                        ],
                    ],
                    '02-02' => [
                        'hours' => [],
                        'data'  => [
                            'id' => 'my_id',
                        ],
                    ],
                ],
            ]
        );

        $this->assertTrue($hours->isClosedAt(new DateTimeImmutable('2020-01-01')));
    }

    /** @test */
    public function it_can_calculate_time_diff()
    {
        $openingHours = OpeningHours::create([
            'monday'    => ['10:00-16:00', '19:30-20:30'],
            'tuesday'   => ['22:30-04:00'],
            'wednesday' => ['07:00-10:00'],
            'thursday'  => ['09:00-12:00'],
            'friday'    => ['09:00-12:00'],
            'saturday'  => [],
            'sunday'    => [],
        ]);

        $this->assertSame(2.0, $openingHours->diffInClosedSeconds(new DateTimeImmutable('Monday 09:59:58'), new DateTimeImmutable('Monday 10:59:58')));
        $this->assertSame(3600.0 - 2.0, $openingHours->diffInOpenSeconds(new DateTimeImmutable('Monday 09:59:58'), new DateTimeImmutable('Monday 10:59:58')));

        if (version_compare(PHP_VERSION, '7.1.0-dev', '>=')) {
            $this->assertSame(1.5, $openingHours->diffInClosedSeconds(new DateTimeImmutable('Monday 09:59:58.5'), new DateTimeImmutable('Monday 10:59:58.5')));
            $this->assertSame(3600.0 - 1.5, $openingHours->diffInOpenSeconds(new DateTimeImmutable('Monday 09:59:58.5'), new DateTimeImmutable('Monday 10:59:58.5')));
        }

        $this->assertSame(1.5 * 60, $openingHours->diffInOpenMinutes(new DateTimeImmutable('Monday 3pm'), new DateTimeImmutable('Monday 8pm')));
        $this->assertSame(3.5 * 60, $openingHours->diffInClosedMinutes(new DateTimeImmutable('Monday 3pm'), new DateTimeImmutable('Monday 8pm')));
        $this->assertSame(18.5, $openingHours->diffInOpenHours(new DateTimeImmutable('2020-06-21 3pm'), new DateTimeImmutable('2020-06-25 2pm')));
        $this->assertSame(76.5, $openingHours->diffInClosedHours(new DateTimeImmutable('2020-06-21 3pm'), new DateTimeImmutable('2020-06-25 2pm')));
        $this->assertSame(-18.5, $openingHours->diffInOpenHours(new DateTimeImmutable('2020-06-25 2pm'), new DateTimeImmutable('2020-06-21 3pm')));
        $this->assertSame(-76.5, $openingHours->diffInClosedHours(new DateTimeImmutable('2020-06-25 2pm'), new DateTimeImmutable('2020-06-21 3pm')));
    }
}
