<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeZone;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_return_the_opening_hours_for_a_regular_week()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $openingHoursForWeek = $openingHours->forWeek();

        $this->assertCount(7, $openingHoursForWeek);
        $this->assertEquals('09:00-18:00', (string) $openingHoursForWeek['monday'][0]);
        $this->assertCount(0, $openingHoursForWeek['tuesday']);
        $this->assertCount(0, $openingHoursForWeek['wednesday']);
        $this->assertCount(0, $openingHoursForWeek['thursday']);
        $this->assertCount(0, $openingHoursForWeek['friday']);
        $this->assertCount(0, $openingHoursForWeek['saturday']);
        $this->assertCount(0, $openingHoursForWeek['sunday']);
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
    public function it_can_set_the_timezone_on_the_openings_hours_object()
    {
        $openingHours = new OpeningHours('Europe/Amsterdam');
        $openingHours->fill([
            'monday' => ['09:00-18:00'],
        ]);

        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 10:00')));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 15:59'))); // 2 Hours difference
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 08:00'))); // 2 Hours difference
        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 06:00'))); // 2 Hours difference

        $this->assertFalse($openingHours->isOpenAt(new DateTime('2016-10-10 06:00', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 09:00', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertTrue($openingHours->isOpenAt(new DateTime('2016-10-10 17:59', new DateTimeZone('Europe/Amsterdam'))));
    }
}
