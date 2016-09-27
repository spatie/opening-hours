<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_determine_whether_its_open_at_a_certain_date_and_time()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $shouldBeOpen = new DateTime('2016-09-26 11:00:00');
        $shouldBeClosed = new DateTime('2016-09-27 11:00:00');

        $this->assertTrue($openingHours->isOpenAt($shouldBeOpen));
        $this->assertFalse($openingHours->isOpenAt($shouldBeClosed));
    }

    /** @test */
    public function it_can_determine_whether_its_open_at_a_certain_date_and_time_on_an_exceptional_day()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'exceptions' => [
                '2016-09-26' => [],
            ],
        ]);

        $shouldBeClosed = new DateTime('2016-09-26 11:00:00');

        $this->assertFalse($openingHours->isOpenAt($shouldBeClosed));
    }
}
