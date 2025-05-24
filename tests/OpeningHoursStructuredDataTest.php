<?php

namespace Spatie\OpeningHours\Test;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursStructuredDataTest extends TestCase
{
    #[Test]
    public function it_can_render_opening_hours_as_an_array_of_structured_data()
    {
        $openingHours = OpeningHours::create([
            'monday' => ['09:00-18:00'],
            'tuesday' => ['09:00-18:00'],
            'wednesday' => ['09:00-12:00', '14:00-18:00'],
            'thursday' => [],
            'friday' => ['09:00-20:00'],
            'exceptions' => [
                '2016-09-26' => ['09:00-12:00'], // Monday
                '2016-09-27' => [], // Tuesday
            ],
        ]);

        $expected = [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Monday',
                'opens' => '09:00',
                'closes' => '18:00',
            ], [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Tuesday',
                'opens' => '09:00',
                'closes' => '18:00',
            ], [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Wednesday',
                'opens' => '09:00',
                'closes' => '12:00',
            ], [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Wednesday',
                'opens' => '14:00',
                'closes' => '18:00',
            ], [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Friday',
                'opens' => '09:00',
                'closes' => '20:00',
            ], [
                '@type' => 'OpeningHoursSpecification',
                'opens' => '09:00',
                'closes' => '12:00',
                'validFrom' => '2016-09-26',
                'validThrough' => '2016-09-26',
            ], [
                '@type' => 'OpeningHoursSpecification',
                'opens' => '00:00',
                'closes' => '00:00',
                'validFrom' => '2016-09-27',
                'validThrough' => '2016-09-27',
            ],
        ];

        $this->assertSame($expected, $openingHours->asStructuredData());

        $openingHours = OpeningHours::create([
            'monday' => [
                'hours' => [
                    '09:00-17:00',
                ],
            ],
        ]);

        $this->assertSame('17:00:00+00:00', $openingHours->asStructuredData('H:i:sP')[0]['closes']);

        $this->assertSame('17:00:00-05:00', $openingHours->asStructuredData('H:i:sP', '-05:00')[0]['closes']);

        $this->assertSame('17:00:00+01:00', $openingHours->asStructuredData('H:i:sP', 'Europe/Paris')[0]['closes']);

        $this->assertSame('17:00:00+12:45', $openingHours->asStructuredData('H:i:sP', new DateTimeZone('+12:45'))[0]['closes']);
    }

    #[Test]
    public function it_can_find_previous_close_time_with_custom_timezone()
    {
        $schedule = [
            'monday' => ['10:00-23:59'],
            'tuesday' => ['10:00-23:59'],
            'wednesday' => ['10:00-23:59'],
            'thursday' => ['10:00-23:59'],
            'friday' => ['10:00-23:59'],
            'saturday' => ['10:00-23:59'],
            'sunday' => ['10:00-23:59'],
        ];

        $openingHours = OpeningHours::createAndMergeOverlappingRanges(
            data: $schedule,
            timezone: 'Australia/Brisbane',
        );

        $now = new DateTime('2025-05-23 09:00:00', new DateTimeZone('Australia/Brisbane'));

        $this->assertSame(
            '2025-05-22 23:59:00 Australia/Brisbane',
            $openingHours->previousClose($now)->format('Y-m-d H:i:s e'),
        );
    }
}
