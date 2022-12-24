<?php

use Spatie\OpeningHours\OpeningHours;

it('can render opening hours as an array of structured data', function () {
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

    expect($openingHours->asStructuredData())->toBe($expected);

    $openingHours = OpeningHours::create([
        'monday' => [
        'hours' =>  [
            '09:00-17:00',
        ],
        ],
    ]);

    expect($openingHours->asStructuredData('H:i:sP')[0]['closes'])->toBe('17:00:00+00:00')
        ->and($openingHours->asStructuredData('H:i:sP', '-05:00')[0]['closes'])->toBe('17:00:00-05:00')
        ->and($openingHours->asStructuredData('H:i:sP', 'Europe/Paris')[0]['closes'])->toBe('17:00:00+01:00')
        ->and($openingHours->asStructuredData('H:i:sP', new DateTimeZone('+12:45'))[0]['closes'])->toBe('17:00:00+12:45');

});
