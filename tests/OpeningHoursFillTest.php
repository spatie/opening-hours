<?php

use Spatie\OpeningHours\Day;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\TimeRange;

it('fills opening hours', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'tuesday' => ['09:00-18:00'],
        'wednesday' => ['09:00-12:00', '14:00-18:00'],
        'thursday' => [],
        'friday' => ['09:00-20:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    expect($openingHours->forDay('monday')[0])->toBeInstanceOf(TimeRange::class)
        ->and('09:00-18:00')->toBe((string)$openingHours->forDay('monday')[0])
        ->and($openingHours->forDay('tuesday')[0])->toBeInstanceOf(TimeRange::class)
        ->and('09:00-18:00')->toBe((string)$openingHours->forDay('tuesday')[0])
        ->and($openingHours->forDay('wednesday')[0])->toBeInstanceOf(TimeRange::class)
        ->and('09:00-12:00')->toBe((string)$openingHours->forDay('wednesday')[0])
        ->and($openingHours->forDay('wednesday')[1])->toBeInstanceOf(TimeRange::class)
        ->and('14:00-18:00')->toBe((string)$openingHours->forDay('wednesday')[1])
        ->and($openingHours->forDay('thursday'))->toHaveCount(0)
        ->and($openingHours->forDay('friday')[0])->toBeInstanceOf(TimeRange::class)
        ->and('09:00-20:00')->toBe((string)$openingHours->forDay('friday')[0])
        ->and($openingHours->forDate(new DateTime('2016-09-26 11:00:00')))->toHaveCount(0)
        ->and($openingHours->forDate(new DateTimeImmutable('2016-09-26 11:00:00')))->toHaveCount(0);

});

it('can map week with a callback', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'tuesday' => ['10:00-18:00'],
        'wednesday' => ['09:00-12:00', '14:00-18:00'],
        'thursday' => [],
        'friday' => ['14:00-20:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    expect(
        $openingHours->map(function (OpeningHoursForDay $ranges) {
            return $ranges->isEmpty() ? null : $ranges->offsetGet(0)->start()->hours();
        })
    )->toEqual([
        'monday' => 9,
        'tuesday' => 10,
        'wednesday' => 9,
        'thursday' => null,
        'friday' => 14,
        'saturday' => null,
        'sunday' => null,
    ]);
});

it('can map exceptions with a callback', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'tuesday' => ['10:00-18:00'],
        'wednesday' => ['09:00-12:00', '14:00-18:00'],
        'thursday' => [],
        'friday' => ['14:00-20:00'],
        'exceptions' => [
        '2016-09-26' => [],
        '10-10' => ['14:00-20:00'],
        ],
    ]);

    expect(
        $openingHours->mapExceptions(function (OpeningHoursForDay $ranges) {
            return $ranges->isEmpty() ? null : $ranges->offsetGet(0)->start()->hours();
        })
    )->toEqual([
        '2016-09-26' => null,
        '10-10' => 14,
    ]);
});

it('can handle empty input', function () {
    $openingHours = OpeningHours::create([]);

    foreach (Day::days() as $dayName) {
        expect($openingHours->forDay($dayName))->toHaveCount(0);
    }
});

it('handles day names in a case insensitive manner', function () {
    $openingHours = OpeningHours::create([
        'Monday' => ['09:00-18:00'],
    ]);

    expect('09:00-18:00')->toBe((string) $openingHours->forDay('monday')[0]);

    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ]);

    expect('09:00-18:00')->toBe((string) $openingHours->forDay('Monday')[0]);
});

it('will throw an exception when using an invalid day name', function () {
    OpeningHours::create(['mmmmonday' => ['09:00-18:00']]);
})->throws(InvalidDayName::class);

it('will throw an exception when using an invalid exception date', function () {
    OpeningHours::create([
        'exceptions' => [
        '25/12/2016' => [],
        ],
    ]);
})->throws(InvalidDate::class);

it('store meta data', function () {
    $hours = OpeningHours::create([
        'monday' => [
        '09:00-12:00',
        '13:00-18:00',
        ],
        'tuesday' => [
        '09:00-12:00',
        '13:00-18:00',
        'data' => 'foobar',
        ],
        'wednesday' => [
        'hours' => ['09:00-12:00'],
        'data' => ['foobar'],
        ],
        'thursday' => [
        [
            'hours' => '09:00-12:00',
            'data' => ['foobar'],
        ],
        '13:00-18:00',
        ],
        'exceptions' => [
        '2011-01-01' => [
            'hours' => ['13:00-18:00'],
            'data' => 'Newyearsday opening times',
        ],
        '2011-01-02' => [
            '13:00-18:00',
            'data' => 'Newyearsday next day',
        ],
        '12-25' => [
            'data' => 'Christmas',
        ],
        ],
    ]);

    expect($hours->exceptions()['2011-01-01']->getData())->toBe('Newyearsday opening times')
        ->and($hours->forDate(new DateTime('2011-01-01'))->getData())->toBe('Newyearsday opening times')
        ->and($hours->exceptions()['2011-01-02']->getData())->toBe('Newyearsday next day')
        ->and($hours->exceptions()['12-25']->getData())->toBe('Christmas')
        ->and($hours->forDate(new DateTime('2011-12-25'))->getData())->toBe('Christmas')
        ->and($hours->forDay('monday')->getData())->toBeNull()
        ->and($hours->forDay('tuesday')->getData())->toBe('foobar')
        ->and($hours->forDay('tuesday')->count())->toBe(2)
        ->and($hours->forDay('wednesday')->getData())->toBe(['foobar'])
        ->and($hours->forDay('wednesday')->count())->toBe(1)
        ->and($hours->forDay('thursday')[0]->getData())->toBe(['foobar'])
        ->and($hours->forDay('thursday')[1]->getData())->toBeNull();

    $hours = OpeningHours::create([
        'monday' => [
        ['09:00-12:00', 'morning'],
        ['13:00-18:00', 'afternoon'],
        ],
    ]);

    expect($hours->forDay('monday')[0]->getData())->toBe('morning')
        ->and($hours->forDay('monday')[1]->getData())->toBe('afternoon');

    $hours = OpeningHours::create([
        'tuesday' => [
        '09:00-12:00',
        '13:00-18:00',
        [
            '19:00-21:00',
            'data' => 'Extra on Tuesday evening',
        ],
        ],
    ]);

    expect(strval($hours->forDay('tuesday')))->toEqual('09:00-12:00,13:00-18:00,19:00-21:00')
        ->and($hours->forDay('tuesday')[1]->getData())->toBeNull()
        ->and($hours->forDay('tuesday')[2]->getData())->toBe('Extra on Tuesday evening');
});

it('handle filters', function () {
    $typicalDay = [
        '08:00-12:00',
        '14:00-18:00',
    ];
    $hours = OpeningHours::create([
        'monday' => $typicalDay,
        'tuesday' => $typicalDay,
        'wednesday' => $typicalDay,
        'thursday' => $typicalDay,
        'friday' => $typicalDay,
        'exceptions' => [
        // Closure in exceptions will be handled as a filter.
        function (DateTimeImmutable $date) {
            if ($date->format('Y-m-d') === $date->modify('first monday of this month')->format('Y-m-d')) {
            // Big lunch each first monday of the month
            return [
                '08:00-11:00',
                '15:00-18:00',
            ];
            }
        },
        ],
        'filters' => [
        function (DateTimeImmutable $date) {
            $year = intval($date->format('Y'));
            $easterMonday = new DateTimeImmutable('2018-03-21 +'.(easter_days($year) + 1).'days');
            if ($date->format('m-d') === $easterMonday->format('m-d')) {
            return []; // Closed on Easter monday
            }
        },
        function (DateTimeImmutable $date) use ($typicalDay) {
            if ($date->format('m') === $date->format('d')) {
            return [
                'hours' => $typicalDay,
                'data' => 'Month equals day',
            ];
            }
        },
        ],
    ]);

    expect($hours->getFilters())->toHaveCount(3)
        ->and($hours->forDate(new DateTimeImmutable('2018-12-03'))->__toString())->toBe('08:00-11:00,15:00-18:00')
        ->and($hours->forDate(new DateTimeImmutable('2018-12-10'))->__toString())->toBe('08:00-12:00,14:00-18:00')
        ->and($hours->forDate(new DateTimeImmutable('2018-04-02'))->__toString())->toBe('')
        ->and($hours->nextOpen(new DateTimeImmutable('2018-03-31'))->format('m-d H:i'))->toBe('04-03 08:00')
        ->and($hours->nextClose(new DateTimeImmutable('2018-12-03'))->format('m-d H:i'))->toBe('12-03 11:00')
        ->and($hours->forDate(new DateTimeImmutable('2018-12-12'))->getData())->toBe('Month equals day');
});

it('should merge ranges on explicitly create from overlapping ranges', function () {
    $hours = OpeningHours::createAndMergeOverlappingRanges([
        'monday' => [
        '08:00-12:00',
        '08:00-12:00',
        '11:30-13:30',
        '13:00-18:00',
        ],
        'tuesday' => [
        '08:00-12:00',
        '11:30-13:30',
        '15:00-18:00',
        '16:00-17:00',
        '19:00-20:00',
        '20:00-21:00',
        ],
    ]);
    $dump = [];
    foreach (['monday', 'tuesday'] as $day) {
        $dump[$day] = [];
        foreach ($hours->forDay($day) as $range) {
        $dump[$day][] = $range->format();
        }
    }

    expect($dump['monday'])->toEqual([
        '08:00-18:00',
    ])
        ->and($dump['tuesday'])->toEqual([
            '08:00-13:30',
            '15:00-18:00',
            '19:00-21:00',
        ]);
});

it('should merge ranges including explicit 24 00', function () {
    $hours = OpeningHours::createAndMergeOverlappingRanges([
        'monday' => [
        '08:00-12:00',
        '12:00-24:00',
        ],
    ]);
    $dump = [];
    foreach ($hours->forDay('monday') as $range) {
        $dump[] = $range->format();
    }

    expect($dump)->toEqual([
        '08:00-24:00',
    ]);
});

it('should reorder ranges', function () {
    $hours = OpeningHours::createAndMergeOverlappingRanges([
        'monday' => [
        '13:00-24:00',
        '08:00-12:00',
        ],
    ]);

    expect($hours->nextOpen(new DateTime('2019-07-06 07:25'))->format('H:i'))->toBe('08:00');
});
