<?php

declare(strict_types=1);

namespace Spatie\OpeningHours\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Day;
use Spatie\OpeningHours\Exceptions\InvalidOpeningHoursSpecification;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursSpecificationParserTest extends TestCase
{
    public function testCreateFromStructuredData(): void
    {
        $openingHoursSpecs = <<<'JSON'
            [
                {
                    "@type": "OpeningHoursSpecification",
                    "opens": "08:00",
                    "closes": "12:00",
                    "dayOfWeek": [
                        "https://schema.org/Monday",
                        "https://schema.org/Tuesday",
                        "https://schema.org/Wednesday",
                        "https://schema.org/Thursday",
                        "https://schema.org/Friday"
                    ]
                },
                {
                    "@type": "OpeningHoursSpecification",
                    "opens": "14:00",
                    "closes": "18:00",
                    "dayOfWeek": [
                        "https://schema.org/Monday",
                        "https://schema.org/Tuesday",
                        "https://schema.org/Wednesday",
                        "https://schema.org/Thursday",
                        "https://schema.org/Friday"
                    ]
                },
                {
                    "@type": "OpeningHoursSpecification",
                    "opens": "08:00:00",
                    "closes": "12:00:00",
                    "dayOfWeek": "https://schema.org/Saturday"
                },
                {
                    "@type": "OpeningHoursSpecification",
                    "opens": "00:00",
                    "closes": "00:00",
                    "dayOfWeek": [
                        "Sunday"
                    ]
                },
                {
                    "@type": "OpeningHoursSpecification",
                    "opens": "00:00",
                    "closes": "00:00",
                    "validFrom": "2023-12-25",
                    "validThrough": "2023-12-25"
                },
                {
                    "@type": "OpeningHoursSpecification",
                    "opens": "09:00",
                    "closes": "18:00",
                    "validFrom": "2023-12-24",
                    "validThrough": "2023-12-24"
                }
            ]
            JSON;

        $openingHours = OpeningHours::createFromStructuredData($openingHoursSpecs);
        $this->assertInstanceOf(OpeningHours::class, $openingHours);

        $this->assertCount(2, $openingHours->forDay('monday'));
        $this->assertCount(2, $openingHours->forDay('tuesday'));
        $this->assertCount(2, $openingHours->forDay('wednesday'));
        $this->assertCount(2, $openingHours->forDay('thursday'));
        $this->assertCount(2, $openingHours->forDay('friday'));
        $this->assertCount(1, $openingHours->forDay('saturday'));
        $this->assertCount(0, $openingHours->forDay('sunday'));

        $this->assertTrue($openingHours->isOpenAt(new \DateTime('2023-11-20 08:00')));
        $this->assertTrue($openingHours->isOpenAt(new \DateTime('2023-11-21 08:00')));
        $this->assertTrue($openingHours->isOpenAt(new \DateTime('2023-11-22 08:00')));
        $this->assertTrue($openingHours->isOpenAt(new \DateTime('2023-11-23 08:00')));
        $this->assertTrue($openingHours->isOpenAt(new \DateTime('2023-11-24 08:00')));
        $this->assertTrue($openingHours->isOpenAt(new \DateTime('2023-11-25 08:00')));
        $this->assertTrue($openingHours->isOpenAt(new \DateTime('2023-11-25 11:59')));
        $this->assertFalse($openingHours->isOpenAt(new \DateTime('2023-11-25 13:00')));
        $this->assertFalse($openingHours->isOpenAt(new \DateTime('2023-11-26 08:00')));

        // Exception Closed on Christmas day
        $this->assertTrue(
            $openingHours->isClosedAt(new \DateTime('2023-12-25 08:00')),
            'Closed on 2023 Monday Christmas day',
        );
        // Exception Opened on Christmas Eve
        $this->assertTrue(
            $openingHours->isOpenAt(new \DateTime('2023-12-24 10:00')),
            'Opened on 2023 Sunday before Christmas day',
        );
    }

    public function testEmptySpecs(): void
    {
        $openingHours = OpeningHours::createFromStructuredData([]);

        $this->assertTrue($openingHours->isAlwaysClosed());
    }

    public function testH24Specs(): void
    {
        $openingHours = OpeningHours::createFromStructuredData([
            [
                'opens' => '00:00',
                'closes' => '23:59',
                'dayOfWeek' => [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                ],
            ],
        ]);

        $this->assertTrue(
            $openingHours->isOpenAt(new DateTimeImmutable('2023-11-27 23:59:34')),
            'As per specs, 23:59 is assumed to mean until end of day',
        );
        $this->assertFalse(
            $openingHours->isOpenAt(new DateTimeImmutable('2023-11-25 23:59:34')),
            'Saturday and Sunday not specified means they are closed',
        );
        $this->assertFalse(
            $openingHours->isAlwaysOpen(),
            'Saturday and Sunday not specified means they are closed',
        );

        $openingHours = OpeningHours::createFromStructuredData([
            [
                'opens' => '00:00',
                'closes' => '23:59',
                'dayOfWeek' => [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                    'Saturday',
                    'Sunday',
                ],
            ],
        ]);

        $this->assertTrue(
            $openingHours->isAlwaysOpen(),
            'As per specs, 23:59 is assumed to mean until end of day',
        );
    }

    public function testClosedDay(): void
    {
        $openingHours = OpeningHours::createFromStructuredData([
            ['dayOfWeek' => 'Monday'],
        ]);

        $this->assertSame('', (string) $openingHours->forDay(Day::MONDAY));
    }

    public function testInvalidJson(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid https://schema.org/OpeningHoursSpecification JSON',
        ));

        OpeningHours::createFromStructuredData('{');
    }

    public function testInvalidDayOfWeek(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 1: Property dayOfWeek must be a string or an array of strings',
        ));

        OpeningHours::createFromStructuredData([
            ['dayOfWeek' => []],
            ['dayOfWeek' => true],
        ]);
    }

    public function testInvalidDayType(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Invalid https://schema.org/OpeningHoursSpecification dayOfWeek',
        ));

        OpeningHours::createFromStructuredData([
            ['dayOfWeek' => [true]],
        ]);
    }

    public function testInvalidDayName(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Invalid https://schema.org Day specification',
        ));

        OpeningHours::createFromStructuredData([
            ['dayOfWeek' => ['Wedmonday']],
        ]);
    }

    public function testUnsupportedPublicHolidays(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: PublicHolidays not supported',
        ));

        OpeningHours::createFromStructuredData([
            ['dayOfWeek' => 'PublicHolidays'],
        ]);
    }

    public function testInvalidValidPair(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Contains neither dayOfWeek nor validFrom and validThrough dates',
        ));

        OpeningHours::createFromStructuredData([
            ['validFrom' => '2023-11-25'],
        ]);
    }

    public function testInvalidOpens(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Invalid opens hour',
        ));

        OpeningHours::createFromStructuredData([
            [
                'dayOfWeek' => 'Monday',
                'opens' => 'noon',
                'closes' => '14:00',
            ],
        ]);
    }

    public function testInvalidCloses(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Invalid closes hour',
        ));

        OpeningHours::createFromStructuredData([
            [
                'dayOfWeek' => 'Monday',
                'opens' => '10:00',
                'closes' => 'noon',
            ],
        ]);
    }

    public function testClosesOnly(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Property opens and closes must be both null or both string',
        ));

        OpeningHours::createFromStructuredData([
            [
                'dayOfWeek' => 'Monday',
                'closes' => '10:00',
            ],
        ]);
    }

    public function testInvalidValidFrom(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Invalid validFrom date',
        ));

        OpeningHours::createFromStructuredData([
            [
                'validFrom' => '11/11/2023',
                'validThrough' => '2023-11-25',
            ],
        ]);
    }

    public function testInvalidValidThrough(): void
    {
        self::expectExceptionObject(new InvalidOpeningHoursSpecification(
            'Invalid openingHoursSpecification item at index 0: Invalid validThrough date',
        ));

        OpeningHours::createFromStructuredData([
            [
                'validFrom' => '2023-11-11',
                'validThrough' => '25/11/20235',
            ],
        ]);
    }
}
