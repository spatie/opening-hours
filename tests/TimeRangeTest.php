<?php

namespace Spatie\OpeningHours\Test;

use Spatie\OpeningHours\TimeRange;

class TimeRangeTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created_from_a_string()
    {
        $this->assertEquals((string) TimeRange::fromString('16:00-18:00'), '16:00-18:00');
    }

    /** @test */
    public function it_can_be_created_from_a_string_with_spaces_around_the_delimiter()
    {
        $this->assertEquals((string) TimeRange::fromString('16:00  - 18:00'), '16:00-18:00');
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_string()
    {
        $this->expectException(\InvalidArgumentException::class);

        TimeRange::fromString('16:00-aa:bb');
    }
}
