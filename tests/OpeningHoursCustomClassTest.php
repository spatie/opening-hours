<?php

namespace Spatie\OpeningHours\Test;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Exceptions\InvalidDateTimeClass;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursCustomClassTest extends TestCase
{
    #[Test]
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

    #[Test]
    public function it_can_use_timezones()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ]);

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-07-25 09:00:00 UTC', $date->format('Y-m-d H:i:s e'));

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 Europe/Oslo'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-07-25 09:00:00 Europe/Oslo', $date->format('Y-m-d H:i:s e'));

        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'timezone' => 'Europe/Oslo',
        ]);

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-07-25 07:00:00 UTC', $date->format('Y-m-d H:i:s e'));

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-08-01 07:00:00 UTC', $date->format('Y-m-d H:i:s e'));

        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ], new DateTimeZone('Europe/Oslo'));
        $openingHours->setOutputTimezone('Europe/Oslo');

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-07-25 09:00:00 Europe/Oslo', $date->format('Y-m-d H:i:s e'));

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-08-01 09:00:00 Europe/Oslo', $date->format('Y-m-d H:i:s e'));

        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'timezone' => [
                'input' => 'Europe/Oslo',
                'output' => 'UTC',
            ],
        ]);

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-07-25 07:00:00 UTC', $date->format('Y-m-d H:i:s e'));

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-08-01 07:00:00 UTC', $date->format('Y-m-d H:i:s e'));
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
        ], 'Europe/Oslo', 'America/New_York');

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-07-25 03:00:00 America/New_York', $date->format('Y-m-d H:i:s e'));

        $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2022-08-01 03:00:00 America/New_York', $date->format('Y-m-d H:i:s e'));
    }

    #[Test]
    public function it_can_use_mocked_time()
    {
        $mock1 = new class extends DateTimeImmutable
        {
            public function __construct($datetime = 'now', ?DateTimeZone $timezone = null)
            {
                parent::__construct('2021-10-11 04:30', $timezone);
            }
        };
        $mock2 = new class extends DateTimeImmutable
        {
            public function __construct($datetime = 'now', ?DateTimeZone $timezone = null)
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

    #[Test]
    public function it_should_refuse_invalid_date_time_class()
    {
        $this->expectException(InvalidDateTimeClass::class);
        OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'dateTimeClass' => DateTimeZone::class,
        ]);
    }
}
