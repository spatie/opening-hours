<?php

declare(strict_types=1);

namespace Spatie\OpeningHours\Test;

use PHPUnit\Framework\TestCase;
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

        $openingHours = OpeningHours::createFromStructuredData(json_decode($openingHoursSpecs, true));
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
            'Closed on 2023 Monday Christmas day'
        );
        // Exception Opened on Christmas Eve
        $this->assertTrue(
            $openingHours->isOpenAt(new \DateTime('2023-12-24 10:00')),
            'Opened on 2023 Sunday before Christmas day'
        );
    }
}
