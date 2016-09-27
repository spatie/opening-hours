<?php

namespace Spatie\OpeningHours\Test;

use Spatie\OpeningHours\Time;

class TimeTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created_from_a_string()
    {
        $this->assertEquals((string) Time::fromString('16:00'), '16:00');
    }

    /** @test */
    public function it_cant_be_created_from_an_invalid_string()
    {
        $this->expectException(\InvalidArgumentException::class);

        Time::fromString('aa:bb');
    }
}
