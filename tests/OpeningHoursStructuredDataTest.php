<?php

namespace Spatie\OpeningHours\Test;

use PHPUnit_Framework_TestCase;
use Spatie\OpeningHours\OpeningHours;

class OpeningHoursStructuredDataTest extends PHPUnit_Framework_TestCase
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
    }
}
