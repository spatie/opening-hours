<?php

namespace Spatie\OpeningHours\Test;

use Spatie\OpeningHours\Time;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\TimeRange;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Exceptions\NonMutableOffsets;
use Spatie\OpeningHours\Exceptions\OverlappingTimeRanges;

class OpeningHoursForDayTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_an_array_of_time_range_strings()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

        $this->assertCount(2, $openingHoursForDay);

        $this->assertInstanceOf(TimeRange::class, $openingHoursForDay[0]);
        $this->assertEquals('09:00-12:00', (string) $openingHoursForDay[0]);

        $this->assertInstanceOf(TimeRange::class, $openingHoursForDay[1]);
        $this->assertEquals('13:00-18:00', (string) $openingHoursForDay[1]);
    }

    /** @test */
    public function it_cant_be_created_when_time_ranges_overlap()
    {
        $this->expectException(OverlappingTimeRanges::class);

        OpeningHoursForDay::fromStrings(['09:00-18:00', '14:00-20:00']);
    }

    /** @test */
    public function it_can_determine_whether_its_open_at_a_time()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-18:00']);

        $this->assertTrue($openingHoursForDay->isOpenAt(Time::fromString('09:00')));
        $this->assertFalse($openingHoursForDay->isOpenAt(Time::fromString('08:00')));
        $this->assertFalse($openingHoursForDay->isOpenAt(Time::fromString('18:00')));
    }

    /** @test */
    public function it_casts_to_string()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

        $this->assertEquals('09:00-12:00,13:00-18:00', (string) $openingHoursForDay);
    }

    /** @test */
    public function it_can_offset_is_existed()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

        $this->assertTrue($openingHoursForDay->offsetExists(0));
        $this->assertTrue($openingHoursForDay->offsetExists(1));
        $this->assertFalse($openingHoursForDay->offsetExists(2));
    }

    /** @test */
    public function it_can_unset_offset()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

        $this->assertNull($openingHoursForDay->offsetUnset(0));
        $this->assertNull($openingHoursForDay->offsetUnset(1));
        $this->assertNull($openingHoursForDay->offsetUnset(2));
    }

    /** @test */
    public function it_can_get_iterator()
    {
        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

        $this->assertCount(2, $openingHoursForDay->getIterator()->getArrayCopy());
    }

    /** @test */
    public function it_cant_set_iterator_item()
    {
        $this->expectException(NonMutableOffsets::class);

        $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

        $openingHoursForDay[0] = TimeRange::fromString('07:00-11:00');
    }
}
