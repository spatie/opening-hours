<?php

namespace Spatie\OpeningHours\Test;

use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Time;

class OpeningHoursForDayTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_determine_whether_its_open_at_a_time()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-18:00']);

        $this->assertTrue($openingHoursForDay->isOpenAt(Time::fromString('09:00')));
        $this->assertFalse($openingHoursForDay->isOpenAt(Time::fromString('08:00')));
        $this->assertFalse($openingHoursForDay->isOpenAt(Time::fromString('18:00')));
    }
}
