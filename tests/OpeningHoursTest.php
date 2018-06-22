<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\OpeningHours;

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
        $this->assertEquals('09:00-18:00', (string)$openingHoursForWeek['monday'][0]);
        $this->assertCount(0, $openingHoursForWeek['tuesday']);
        $this->assertCount(0, $openingHoursForWeek['wednesday']);
        $this->assertCount(0, $openingHoursForWeek['thursday']);
        $this->assertCount(0, $openingHoursForWeek['friday']);
        $this->assertCount(0, $openingHoursForWeek['saturday']);
        $this->assertCount(0, $openingHoursForWeek['sunday']);
    }

    /** @test */
    public function it_can_return_combined_opening_hours_for_a_regular_week()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'tuesday' => ['09:00-18:00'],
            'wednesday' => ['11:00-15:00'],
            'thursday' => ['11:00-15:00'],
            'friday' => ['12:00-14:00'],
        ]);

        $openingHoursForWeek = $openingHours->forWeekCombined();

        $this->assertCount(4, $openingHoursForWeek);
        $this->assertEquals('11:00-15:00', $openingHoursForWeek['wednesday']['opening_hours']);
        $this->assertEquals('thursday', array_values($openingHoursForWeek['wednesday']['days'])[1]);
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
            'monday' => ['09:00-18:00'],
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
        $this->assertEquals('09:00-18:00', $openingHoursForMonday[0]);

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
    }

    /** @test */
    public function it_can_determine_that_its_regularly_closed_on_a_week_day()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $this->assertFalse($openingHours->isClosedOn('monday'));
        $this->assertTrue($openingHours->isClosedOn('tuesday'));
    }

    /** @test */
    public function it_can_return_the_opening_hours_for_a_specific_date()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $openingHoursForMonday1909 = $openingHours->forDate(new DateTime('2016-09-19 00:00:00'));
        $openingHoursForMonday2609 = $openingHours->forDate(new DateTime('2016-09-26 00:00:00'));

        $this->assertCount(1, $openingHoursForMonday1909);
        $this->assertEquals('09:00-18:00', $openingHoursForMonday1909[0]);

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

        $shouldBeOpenAlternativeDate = date_create_immutable('2016-09-26 11:12:13.123456');
        $this->assertTrue($openingHours->isOpenAt($shouldBeOpenAlternativeDate));
        $this->assertFalse($openingHours->isClosedAt($shouldBeOpenAlternativeDate));

        $shouldBeClosedBecauseOfTime = new DateTime('2016-09-26 20:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosedBecauseOfTime));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosedBecauseOfTime));

        $shouldBeClosedBecauseOfDay = new DateTime('2016-09-27 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosedBecauseOfDay));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosedBecauseOfDay));
    }

    /** @test */
    public function it_can_determine_that_its_open_at_a_certain_date_and_time_on_an_exceptional_day()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $shouldBeClosed = new DateTime('2016-09-26 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosed));
        $this->assertTrue($openingHours->isClosedAt($shouldBeClosed));
    }

    /** @test */
    public function it_can_determine_that_its_open_at_a_certain_date_and_time_on_an_recurring_exceptional_day()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'exceptions' => [
                '01-01' => [],
                '12-25' => ['09:00-12:00'],
                '12-26' => [],
            ],
        ]);

        $closedOnNewYearDay = new DateTime('2017-01-01 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnNewYearDay));
        $this->assertTrue($openingHours->isClosedAt($closedOnNewYearDay));

        $closedOnSecondChristmasDay = new DateTime('2025-12-16 12:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnSecondChristmasDay));
        $this->assertTrue($openingHours->isClosedAt($closedOnSecondChristmasDay));

        $openOnChristmasMorning = new DateTime('2025-12-25 10:00:00');
        $this->assertTrue($openingHours->isOpenAt($openOnChristmasMorning));
        $this->assertFalse($openingHours->isClosedAt($openOnChristmasMorning));
    }

    /** @test */
    public function it_can_prioritize_exceptions_by_giving_full_dates_priority()
    {
        $openingHours = OpeningHours::create([
            'exceptions' => [
                '2018-01-01' => ['09:00-18:00'],
                '01-01' => [],
                '12-25' => ['09:00-12:00'],
                '12-26' => [],
            ],
        ]);

        $openOnNewYearDay2018 = new DateTime('2018-01-01 11:00:00');
        $this->assertTrue($openingHours->isOpenAt($openOnNewYearDay2018));
        $this->assertFalse($openingHours->isClosedAt($openOnNewYearDay2018));

        $closedOnNewYearDay2019 = new DateTime('2019-01-01 11:00:00');
        $this->assertFalse($openingHours->isOpenAt($closedOnNewYearDay2019));
        $this->assertTrue($openingHours->isClosedAt($closedOnNewYearDay2019));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_non_working_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-11:00', '13:00-19:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 12:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertEquals('2016-09-26 13:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_working_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-11:00', '13:00-19:00'],
            'tuesday' => ['10:00-11:00', '14:00-19:00'],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 16:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertEquals('2016-09-27 10:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_next_open_hours_from_early_morning()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-11:00', '13:00-19:00'],
            'tuesday' => ['10:00-11:00', '14:00-19:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 04:00:00'));

        $this->assertInstanceOf(DateTime::class, $nextTimeOpen);
        $this->assertEquals('2016-09-27 10:00:00', $nextTimeOpen->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_set_the_timezone_on_the_openings_hours_object()
    {
        $openingHours = new OpeningHours('Europe/Amsterdam');
        $openingHours->fill([
            'monday' => ['09:00-18:00'],
            'exceptions' => [
                '2016-11-14' => ['09:00-13:00'],
            ],
        ]);

        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 10:00')));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 15:59')));
        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 08:00')));
        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 06:00')));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 06:00',
            new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:00',
            new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 17:59',
            new DateTimeZone('Europe/Amsterdam'))));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-11-14 17:59',
            new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-11-14 12:59',
            new DateTimeZone('Europe/Amsterdam'))));

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-11-14 15:59',
            new DateTimeZone('America/Denver'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:59',
            new DateTimeZone('America/Denver'))));

        date_default_timezone_set('America/Denver');
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:59')));
        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 10:00')));
    }

    /** @test */
    public function it_can_determine_that_its_open_now()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['00:00-23:59'],
            'tuesday' => ['00:00-23:59'],
            'wednesday' => ['00:00-23:59'],
            'thursday' => ['00:00-23:59'],
            'friday' => ['00:00-23:59'],
            'saturday' => ['00:00-23:59'],
            'sunday' => ['00:00-23:59'],
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
            'monday' => ['09:00-18:00'],
            'tuesday' => ['09:00-18:00'],
            'wednesday' => ['09:00-18:00'],
            'thursday' => ['09:00-18:00'],
            'friday' => ['09:00-18:00'],
            'saturday' => [],
            'sunday' => [],
        ]);

        $this->assertEquals(['saturday', 'sunday'], $openingHours->regularClosingDays());
    }

    /** @test */
    public function it_can_retrieve_regular_closing_days_as_iso_numbers()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'tuesday' => ['09:00-18:00'],
            'wednesday' => ['09:00-18:00'],
            'thursday' => ['09:00-18:00'],
            'friday' => ['09:00-18:00'],
            'saturday' => [],
            'sunday' => [],
        ]);

        $this->assertEquals([6, 7], $openingHours->regularClosingDaysISO());
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
        $this->assertEquals('2017-06-01', $exceptionalClosingDates[0]->format('Y-m-d'));
        $this->assertEquals('2017-06-02', $exceptionalClosingDates[1]->format('Y-m-d'));
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
    public function it_can_set_the_timezone()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['00:00-16:00'],
        ]);
        $openingHours->setTimezone('Asia/Taipei');
        $openingHoursForWeek = $openingHours->forWeek();

        $this->assertCount(7, $openingHoursForWeek);
        $this->assertEquals('00:00-16:00', (string)$openingHoursForWeek['monday'][0]);
        $this->assertCount(0, $openingHoursForWeek['tuesday']);
        $this->assertCount(0, $openingHoursForWeek['wednesday']);
        $this->assertCount(0, $openingHoursForWeek['thursday']);
        $this->assertCount(0, $openingHoursForWeek['friday']);
        $this->assertCount(0, $openingHoursForWeek['saturday']);
        $this->assertCount(0, $openingHoursForWeek['sunday']);
    }
}
