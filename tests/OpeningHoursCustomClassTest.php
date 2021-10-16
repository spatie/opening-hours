<?php

namespace Spatie\OpeningHours\Test;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Exceptions\InvalidDateTimeClass;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursCustomClassTest extends TestCase
{
    protected function setUp(): void
    {
        date_default_timezone_set('UTC');
    }

    /** @test */
    public function it_can_use_immutable_date_time()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'dateTimeClass' => DateTimeImmutable::class,
        ]);

        $date = $openingHours->nextOpen(new DateTimeImmutable('2021-10-11 04:30'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2021-10-11 09:00:00', $date->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_use_mocked_time()
    {
        $mock1 = new class extends DateTimeImmutable
        {
            public function __construct($datetime = 'now', DateTimeZone $timezone = null)
            {
                parent::__construct('2021-10-11 04:30', $timezone);
            }
        };
        $mock2 = new class extends DateTimeImmutable
        {
            public function __construct($datetime = 'now', DateTimeZone $timezone = null)
            {
                parent::__construct('2021-10-11 09:30', $timezone);
            }
        };

        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'dateTimeClass' => get_class($mock1),
        ]);

        $this->assertFalse($openingHours->isOpen());

        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'dateTimeClass' => get_class($mock2),
        ]);

        $this->assertTrue($openingHours->isOpen());
    }

    /** @test */
    public function it_should_refuse_invalid_date_time_class()
    {
        $this->expectException(InvalidDateTimeClass::class);
        OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'dateTimeClass' => DateTimeZone::class,
        ]);
    }
}
