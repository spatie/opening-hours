<?php

namespace Spatie\OpeningHours\Test;

use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursStructuredDataTest extends TestCase
{
    /** @test */
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

        $this->assertEquals($expected, $openingHours->asStructuredData());

        $openingHours = OpeningHours::create([
            'monday' => [
                'hours' =>  [
                    '09:00-17:00',
                ],
            ],
        ]);

        $this->assertEquals('17:00:00+00:00', $openingHours->asStructuredData('H:i:sP')[0]['closes']);

        $this->assertEquals('17:00:00-05:00', $openingHours->asStructuredData('H:i:sP', '-05:00')[0]['closes']);

        $this->assertEquals('17:00:00+01:00', $openingHours->asStructuredData('H:i:sP', 'Europe/Paris')[0]['closes']);

        $this->assertEquals('17:00:00+12:45', $openingHours->asStructuredData('H:i:sP', new DateTimeZone('+12:45'))[0]['closes']);
    }
}
