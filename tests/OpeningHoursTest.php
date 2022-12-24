<?php

use Spatie\OpeningHours\Exceptions\MaximumLimitExceeded;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Test\CustomDate;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

it('can return the opening hours for a regular week', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ]);

    $openingHoursForWeek = $openingHours->forWeek();

    expect($openingHoursForWeek)->toHaveCount(7)
        ->and((string)$openingHoursForWeek['monday'][0])->toBe('09:00-18:00')
        ->and($openingHoursForWeek['tuesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['wednesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['thursday'])->toHaveCount(0)
        ->and($openingHoursForWeek['friday'])->toHaveCount(0)
        ->and($openingHoursForWeek['saturday'])->toHaveCount(0)
        ->and($openingHoursForWeek['sunday'])->toHaveCount(0);
});

it('can return consecutive opening hours for a regular week', function () {
    $openingHours = OpeningHours::create([
        'monday'    => [],
        'tuesday'   => ['09:00-18:00'],
        'wednesday' => ['09:00-18:00'],
        'thursday'  => ['09:00-18:00'],
        'friday'    => ['09:00-20:00'],
        'saturday'  => ['09:00-17:00'],
        'sunday'    => [],
    ]);

    $openingHoursForWeek = $openingHours->forWeekConsecutiveDays();

    expect($openingHoursForWeek)->toHaveCount(5)
        ->and($openingHoursForWeek['tuesday']['opening_hours'])->toBeInstanceOf(OpeningHoursForDay::class)
        ->and((string)$openingHoursForWeek['tuesday']['opening_hours'])->toBe('09:00-18:00')
        ->and(array_values($openingHoursForWeek['tuesday']['days'])[1])->toBe('wednesday');

    $openingHours = OpeningHours::create([
        'monday'    => [],
        'tuesday'   => ['09:00-18:00'],
        'wednesday' => ['09:00-15:00'],
        'thursday'  => ['09:00-18:00'],
        'friday'    => ['09:00-18:00'],
        'saturday'  => ['09:00-15:00'],
        'sunday'    => [],
    ]);

    $dump = array_map(function ($data) {
        return implode(', ', $data['days']).': '.((string) $data['opening_hours']);
    }, $openingHours->forWeekConsecutiveDays());

    expect($dump)->toEqual([
        'monday'    => 'monday: ',
        'tuesday'   => 'tuesday: 09:00-18:00',
        'wednesday' => 'wednesday: 09:00-15:00',
        'thursday'  => 'thursday, friday: 09:00-18:00',
        'saturday'  => 'saturday: 09:00-15:00',
        'sunday'    => 'sunday: ',
    ]);

    $openingHours = OpeningHours::create([
        'tuesday'   => ['09:00-18:00'],
        'wednesday' => ['09:00-15:00'],
        'thursday'  => ['09:00-18:00'],
        'friday'    => ['09:00-18:00'],
        'saturday'  => ['09:00-15:00'],
        'sunday'    => [],
        'monday'    => [],
    ]);

    $dump = array_map(function ($data) {
        return implode(', ', $data['days']).': '.((string) $data['opening_hours']);
    }, $openingHours->forWeekConsecutiveDays());

    expect($dump)->toEqual([
        'monday'    => 'monday: ',
        'tuesday'   => 'tuesday: 09:00-18:00',
        'wednesday' => 'wednesday: 09:00-15:00',
        'thursday'  => 'thursday, friday: 09:00-18:00',
        'saturday'  => 'saturday: 09:00-15:00',
        'sunday'    => 'sunday: ',
    ]);
});

it('can return combined opening hours for a regular week', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['09:00-18:00'],
        'tuesday'   => ['09:00-18:00'],
        'wednesday' => ['11:00-15:00'],
        'thursday'  => ['11:00-15:00'],
        'friday'    => ['12:00-14:00'],
    ]);

    $openingHoursForWeek = $openingHours->forWeekCombined();

    expect($openingHoursForWeek)->toHaveCount(4)
        ->and($openingHoursForWeek['wednesday']['opening_hours'])->toBeInstanceOf(OpeningHoursForDay::class)
        ->and((string)$openingHoursForWeek['wednesday']['opening_hours'])->toBe('11:00-15:00')
        ->and(array_values($openingHoursForWeek['wednesday']['days'])[1])->toBe('thursday');

    $openingHours = OpeningHours::create([
        'monday'    => [],
        'tuesday'   => ['09:00-18:00'],
        'wednesday' => ['09:00-15:00'],
        'thursday'  => ['09:00-18:00'],
        'friday'    => ['09:00-18:00'],
        'saturday'  => ['09:00-15:00'],
        'sunday'    => [],
    ]);

    $dump = array_map(function ($data) {
        return implode(', ', $data['days']).': '.((string) $data['opening_hours']);
    }, $openingHours->forWeekCombined());

    expect($dump)->toEqual([
        'monday'    => 'monday, sunday: ',
        'tuesday'   => 'tuesday, thursday, friday: 09:00-18:00',
        'wednesday' => 'wednesday, saturday: 09:00-15:00',
    ]);
});

it('can validate the opening hours', function () {
    $valid = OpeningHours::isValid([
        'monday' => ['09:00-18:00'],
    ]);

    $invalid = OpeningHours::isValid([
        'notaday' => ['18:00-09:00'],
    ]);

    expect($valid)->toBeTrue()
        ->and($invalid)->toBeFalse();
});

it('can return the exceptions', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-18:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    $exceptions = $openingHours->exceptions();

    expect($exceptions)->toHaveCount(1)
        ->and($exceptions['2016-09-26'])->toHaveCount(0);
});

it('can return the opening hours for a regular week day', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ]);

    $openingHoursForMonday = $openingHours->forDay('monday');
    expect($openingHoursForMonday)->toHaveCount(1)
        ->and($openingHoursForMonday[0])->toBeInstanceOf(TimeRange::class)
        ->and((string)$openingHoursForMonday[0])->toBe('09:00-18:00');

    $openingHoursForTuesday = $openingHours->forDay('tuesday');
    expect($openingHoursForTuesday)->toHaveCount(0);
});

it('can determine that its regularly open on a week day', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ]);

    expect($openingHours->isOpenOn('monday'))->toBeTrue()
        ->and($openingHours->isOpenOn('tuesday'))->toBeFalse()
        ->and($openingHours->isOpenOn('2019-08-31'))->toBeFalse()
        ->and($openingHours->isOpenOn('2019-09-01'))->toBeFalse()
        ->and($openingHours->isOpenOn('2020-08-31'))->toBeTrue()
        ->and($openingHours->isOpenOn('2020-09-01'))->toBeFalse()
        ->and($openingHours->isOpenOn((new DateTime('First Monday of January'))->format('m-d')))->toBeTrue()
        ->and($openingHours->isOpenOn((new DateTime('First Tuesday of January'))->format('m-d')))->toBeFalse();
});

it('can determine that its regularly closed on a week day', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ]);

    expect($openingHours->isClosedOn('monday'))->toBeFalse()
        ->and($openingHours->isClosedOn('tuesday'))->toBeTrue()
        ->and($openingHours->isClosedOn('2019-08-31'))->toBeTrue()
        ->and($openingHours->isClosedOn('2019-09-01'))->toBeTrue()
        ->and($openingHours->isClosedOn('2020-08-31'))->toBeFalse()
        ->and($openingHours->isClosedOn('2020-09-01'))->toBeTrue()
        ->and($openingHours->isClosedOn((new DateTime('First Monday of January'))->format('m-d')))->toBeFalse()
        ->and($openingHours->isClosedOn((new DateTime('First Tuesday of January'))->format('m-d')))->toBeTrue();
});

it('can return the opening hours for a specific date', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-18:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    $openingHoursForMonday1909 = $openingHours->forDate(new DateTime('2016-09-19 00:00:00'));
    $openingHoursForMonday2609 = $openingHours->forDate(new DateTime('2016-09-26 00:00:00'));

    expect($openingHoursForMonday1909)->toHaveCount(1)
        ->and($openingHoursForMonday1909[0])->toBeInstanceOf(TimeRange::class)
        ->and((string)$openingHoursForMonday1909[0])->toBe('09:00-18:00')
        ->and($openingHoursForMonday2609)->toHaveCount(0);

    $openingHoursForMonday1909 = $openingHours->forDate(new DateTimeImmutable('2016-09-19 00:00:00'));
    $openingHoursForMonday2609 = $openingHours->forDate(new DateTimeImmutable('2016-09-26 00:00:00'));

    expect($openingHoursForMonday1909)->toHaveCount(1)
        ->and($openingHoursForMonday1909[0])->toBeInstanceOf(TimeRange::class)
        ->and((string)$openingHoursForMonday1909[0])->toBe('09:00-18:00')
        ->and($openingHoursForMonday2609)->toHaveCount(0);

});

it('can determine that its open at a certain date and time', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ]);

    $shouldBeOpen = new DateTime('2016-09-26 11:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue()
        ->and($openingHours->isClosedAt($shouldBeOpen))->toBeFalse();

    $shouldBeOpen = new DateTimeImmutable('2016-09-26 11:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue()
        ->and($openingHours->isClosedAt($shouldBeOpen))->toBeFalse();

    $shouldBeOpenAlternativeDate = date_create_immutable('2016-09-26 11:12:13.123456');
    expect($openingHours->isOpenAt($shouldBeOpenAlternativeDate))->toBeTrue()
        ->and($openingHours->isClosedAt($shouldBeOpenAlternativeDate))->toBeFalse();

    $shouldBeClosedBecauseOfTime = new DateTime('2016-09-26 20:00:00');
    expect($openingHours->isOpenAt($shouldBeClosedBecauseOfTime))->toBeFalse()
        ->and($openingHours->isClosedAt($shouldBeClosedBecauseOfTime))->toBeTrue();

    $shouldBeClosedBecauseOfTime = new DateTimeImmutable('2016-09-26 20:00:00');
    expect($openingHours->isOpenAt($shouldBeClosedBecauseOfTime))->toBeFalse()
        ->and($openingHours->isClosedAt($shouldBeClosedBecauseOfTime))->toBeTrue();

    $shouldBeClosedBecauseOfDay = new DateTime('2016-09-27 11:00:00');
    expect($openingHours->isOpenAt($shouldBeClosedBecauseOfDay))->toBeFalse()
        ->and($openingHours->isClosedAt($shouldBeClosedBecauseOfDay))->toBeTrue();

    $shouldBeClosedBecauseOfDay = new DateTimeImmutable('2016-09-27 11:00:00');
    expect($openingHours->isOpenAt($shouldBeClosedBecauseOfDay))->toBeFalse()
        ->and($openingHours->isClosedAt($shouldBeClosedBecauseOfDay))->toBeTrue();
});

it('can determine that its open at a certain date and time on an exceptional day', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-18:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    $shouldBeClosed = new DateTime('2016-09-26 11:00:00');
    expect($openingHours->isOpenAt($shouldBeClosed))->toBeFalse()
        ->and($openingHours->isClosedAt($shouldBeClosed))->toBeTrue();

    $shouldBeClosed = new DateTimeImmutable('2016-09-26 11:00:00');
    expect($openingHours->isOpenAt($shouldBeClosed))->toBeFalse()
        ->and($openingHours->isClosedAt($shouldBeClosed))->toBeTrue();
});

it('can determine that its open at a certain date and time on an recurring exceptional day', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-18:00'],
        'exceptions' => [
        '01-01' => [],
        '12-25' => ['09:00-12:00'],
        '12-26' => [],
        ],
    ]);

    $closedOnNewYearDay = new DateTime('2017-01-01 11:00:00');
    expect($openingHours->isOpenAt($closedOnNewYearDay))->toBeFalse()
        ->and($openingHours->isClosedAt($closedOnNewYearDay))->toBeTrue();

    $closedOnNewYearDay = new DateTimeImmutable('2017-01-01 11:00:00');
    expect($openingHours->isOpenAt($closedOnNewYearDay))->toBeFalse()
        ->and($openingHours->isClosedAt($closedOnNewYearDay))->toBeTrue();

    $closedOnSecondChristmasDay = new DateTime('2025-12-16 12:00:00');
    expect($openingHours->isOpenAt($closedOnSecondChristmasDay))->toBeFalse()
        ->and($openingHours->isClosedAt($closedOnSecondChristmasDay))->toBeTrue();

    $closedOnSecondChristmasDay = new DateTimeImmutable('2025-12-16 12:00:00');
    expect($openingHours->isOpenAt($closedOnSecondChristmasDay))->toBeFalse()
        ->and($openingHours->isClosedAt($closedOnSecondChristmasDay))->toBeTrue();

    $openOnChristmasMorning = new DateTime('2025-12-25 10:00:00');
    expect($openingHours->isOpenAt($openOnChristmasMorning))->toBeTrue()
        ->and($openingHours->isClosedAt($openOnChristmasMorning))->toBeFalse();

    $openOnChristmasMorning = new DateTimeImmutable('2025-12-25 10:00:00');
    expect($openingHours->isOpenAt($openOnChristmasMorning))->toBeTrue()
        ->and($openingHours->isClosedAt($openOnChristmasMorning))->toBeFalse();
});

it('can prioritize exceptions by giving full dates priority', function () {
    $openingHours = OpeningHours::create([
        'exceptions' => [
        '2018-01-01' => ['09:00-18:00'],
        '01-01'      => [],
        '12-25'      => ['09:00-12:00'],
        '12-26'      => [],
        ],
    ]);

    $openOnNewYearDay2018 = new DateTime('2018-01-01 11:00:00');
    expect($openingHours->isOpenAt($openOnNewYearDay2018))->toBeTrue()
        ->and($openingHours->isClosedAt($openOnNewYearDay2018))->toBeFalse();

    $openOnNewYearDay2018 = new DateTimeImmutable('2018-01-01 11:00:00');
    expect($openingHours->isOpenAt($openOnNewYearDay2018))->toBeTrue()
        ->and($openingHours->isClosedAt($openOnNewYearDay2018))->toBeFalse();

    $closedOnNewYearDay2019 = new DateTime('2019-01-01 11:00:00');
    expect($openingHours->isOpenAt($closedOnNewYearDay2019))->toBeFalse()
        ->and($openingHours->isClosedAt($closedOnNewYearDay2019))->toBeTrue();

    $closedOnNewYearDay2019 = new DateTimeImmutable('2019-01-01 11:00:00');
    expect($openingHours->isOpenAt($closedOnNewYearDay2019))->toBeFalse()
        ->and($openingHours->isClosedAt($closedOnNewYearDay2019))->toBeTrue();
});

it('can handle consecutive open hours', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['09:00-24:00'],
        'tuesday'   => ['00:00-24:00'],
        'wednesday' => ['00:00-03:00', '09:00-24:00'],
        'friday'    => ['00:00-03:00'],
    ]);

    $monday = new DateTime('2019-02-04 11:00:00');
    $dayHours = $openingHours->forDay('monday');
    expect($openingHours->isOpenAt($monday))->toBeTrue()
        ->and($openingHours->isClosedAt($monday))->toBeFalse()
        ->and($dayHours->nextOpenRange(Time::fromString('08:00'))->format())->toBe('09:00-24:00')
        ->and($dayHours->nextOpenRange(Time::fromString('10:00')))->toBeFalse()
        ->and($dayHours->previousOpenRange(Time::fromString('10:00')))->toBeFalse()
        ->and($dayHours->nextCloseRange(Time::fromString('08:00'))->format())->toBe('09:00-24:00')
        ->and($dayHours->previousCloseRange(Time::fromString('10:00')))->toBeFalse()
        ->and($dayHours->nextCloseRange(Time::fromString('10:00'))->format())->toBe('09:00-24:00')
        ->and($openingHours->nextClose($monday)->format('Y-m-d H:i:s'))->toBe('2019-02-06 03:00:00')
        ->and($openingHours->nextOpen($monday)->format('Y-m-d H:i:s'))->toBe('2019-02-06 09:00:00')
        ->and($openingHours->previousClose($monday)->format('Y-m-d H:i:s'))->toBe('2019-02-01 03:00:00')
        ->and($openingHours->previousOpen($monday)->format('Y-m-d H:i:s'))->toBe('2019-02-04 09:00:00')
        ->and($openingHours->previousOpen(new DateTime('2019-02-04 08:50:00'))->format('Y-m-d H:i:s'))->toBe('2019-02-01 00:00:00');

    $monday = new DateTimeImmutable('2019-02-04 11:00:00');
    expect($openingHours->isOpenAt($monday))->toBeTrue()
        ->and($openingHours->isClosedAt($monday))->toBeFalse()
        ->and($openingHours->nextClose($monday)->format('Y-m-d H:i:s'))->toBe('2019-02-06 03:00:00')
        ->and($openingHours->nextOpen($monday)->format('Y-m-d H:i:s'))->toBe('2019-02-06 09:00:00');

    $wednesday = new DateTime('2019-02-06 09:00:00');
    $dayHours = $openingHours->forDay('wednesday');
    expect($openingHours->isOpenAt($wednesday))->toBeTrue()
        ->and($openingHours->isClosedAt($wednesday))->toBeFalse()
        ->and($dayHours->previousCloseRange(Time::fromString('08:00'))->format())->toBe('00:00-03:00')
        ->and($dayHours->previousCloseRange(Time::fromString('08:00'))->format())->toBe('00:00-03:00')
        ->and($dayHours->previousOpen(Time::fromString('08:00'))->format())->toBe('00:00')
        ->and($dayHours->previousClose(Time::fromString('08:00'))->format())->toBe('03:00')
        ->and($openingHours->nextClose($wednesday)->format('Y-m-d H:i:s'))->toBe('2019-02-07 00:00:00')
        ->and($openingHours->nextOpen($wednesday)->format('Y-m-d H:i:s'))->toBe('2019-02-08 00:00:00')
        ->and($openingHours->previousClose($wednesday)->format('Y-m-d H:i:s'))->toBe('2019-02-06 03:00:00')
        ->and($openingHours->previousOpen($wednesday)->format('Y-m-d H:i:s'))->toBe('2019-02-04 09:00:00');

    $wednesday = new DateTimeImmutable('2019-02-06 09:00:00');
    expect($openingHours->isOpenAt($wednesday))->toBeTrue()
        ->and($openingHours->isClosedAt($wednesday))->toBeFalse()
        ->and($openingHours->nextClose($wednesday)->format('Y-m-d H:i:s'))->toBe('2019-02-07 00:00:00')
        ->and($openingHours->nextOpen($wednesday)->format('Y-m-d H:i:s'))->toBe('2019-02-08 00:00:00');

    $friday = new DateTimeImmutable('2019-02-08 09:00:00');
    expect($openingHours->previousClose($friday)->format('Y-m-d H:i:s'))->toBe('2019-02-08 03:00:00')
        ->and($openingHours->previousOpen($friday)->format('Y-m-d H:i:s'))->toBe('2019-02-08 00:00:00');

    $friday = new DateTimeImmutable('2019-02-08 02:00:00');
    expect($openingHours->previousClose($friday)->format('Y-m-d H:i:s'))->toBe('2019-02-07 00:00:00')
        ->and($openingHours->previousOpen($friday)->format('Y-m-d H:i:s'))->toBe('2019-02-08 00:00:00');

    $friday = new DateTimeImmutable('2022-08-05 03:00:00.000001');
    expect($openingHours->previousClose($friday)->format('Y-m-d H:i:s'))->toBe('2022-08-05 03:00:00');

    $friday = new DateTimeImmutable('2022-08-05 00:00:00.000000');
    expect($openingHours->previousClose($friday)->format('Y-m-d H:i:s'))->toBe('2022-08-04 00:00:00');
});

it('can determine next open hours from non working date time', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-11:00', '13:00-19:00'],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 12:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 12:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 09:00:00');
});

it('can determine next open hours from edges time', function () {
    $openingHours = OpeningHours::create([
        'monday'  => ['09:00-11:00', '13:00-19:00'], // 2016-09-26
        'tuesday' => ['09:00-11:00', '13:00-19:00'], // 2016-09-27
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 00:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 09:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 00:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-20 13:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 09:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 09:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-20 13:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 11:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 11:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 09:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 12:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 12:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 09:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 13:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 09:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 13:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 09:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 19:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 09:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 19:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 23:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 09:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-26 23:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-27 23:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 13:00:00');

    $previousTimeOpen = $openingHours->previousOpen(new DateTime('2016-09-27 08:00:00'));

    expect($previousTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($previousTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');
});

it('can determine next open hours from mixed structures', function () {
    $openingHours = OpeningHours::create([
        'monday' => [
        [
            'hours' => '09:00-11:00',
            'data'  => ['foobar'],
        ],
        '13:00-19:00',
        ],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 00:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-11 09:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 00:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-11 11:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 09:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-11 13:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 09:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-11 11:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 10:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-11 13:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 10:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-11 11:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 11:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-11 13:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 11:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-11 19:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 12:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-11 13:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 12:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-11 19:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 13:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-18 09:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 13:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-11 19:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 15:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-18 09:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 15:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-11 19:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 19:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-18 09:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 19:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-18 11:00:00');

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2019-02-11 21:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2019-02-18 09:00:00');

    $nextTimeClose = $openingHours->nextClose(new DateTime('2019-02-11 21:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2019-02-18 11:00:00');
});

it('can determine next open hours from non working date time immutable', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-11:00', '13:00-19:00'],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 12:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 13:00:00');

    /** @var CustomDate $nextTimeOpen */
    $nextTimeOpen = $openingHours->nextOpen(new CustomDate('2016-09-26 12:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(CustomDate::class)
        ->and($nextTimeOpen->foo())->toBe('2016-09-26 13:00:00');
});

it('can determine next close hours from non working date time', function () {
    $ranges = [
        'monday'     => ['09:00-18:00'],
        /* all the default week settings */
        'exceptions' => [
        // add non-dynamic exceptions, else let empty
        ],
    ];
    $dynamicClosedRanges = [
        '2016-11-07' => ['12:30-13:00'],
    ];
    foreach ($dynamicClosedRanges as $day => $closedRanges) {
        $weekDay = strtolower((new DateTime($day))->format('l'));
        $dayRanges = \Spatie\OpeningHours\OpeningHoursForDay::fromStrings($ranges[$weekDay]);
        $newRanges = [];

        foreach ($dayRanges as $dayRange) {
        /* @var \Spatie\OpeningHours\TimeRange $dayRange */
        foreach ($closedRanges as $exceptionRange) {
            $range = \Spatie\OpeningHours\TimeRange::fromString($exceptionRange);
            if ($dayRange->containsTime($range->start()) && $dayRange->containsTime($range->end())) {
            $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($dayRange->start()->format().'-'.$range->start()->format())->format();
            $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($range->end()->format().'-'.$dayRange->end()->format())->format();
            continue 2;
            }
            if ($dayRange->containsTime($range->start())) {
            $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($dayRange->start()->format().'-'.$range->start()->format())->format();
            continue 2;
            }
            if ($dayRange->containsTime($range->end())) {
            $newRanges[] = \Spatie\OpeningHours\TimeRange::fromString($range->end()->format().'-'.$dayRange->end()->format())->format();
            continue 2;
            }
        }

        $newRanges[] = $dayRange->format();
        }

        $ranges['exceptions'][$day] = $newRanges;
    }

    $openingHours = OpeningHours::createAndMergeOverlappingRanges($ranges);

    expect(strval($openingHours->forDate(new DateTime('2016-11-07'))))->toEqual('09:00-12:30,13:00-18:00')
        ->and(strval($openingHours->forDate(new DateTime('2016-11-14'))))->toBe('09:00-18:00');
});

it('can determine next close hours from non working date time immutable', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-11:00', '13:00-19:00'],
    ]);

    $nextTimeOpen = $openingHours->nextClose(new DateTimeImmutable('2016-09-26 12:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-26 19:00:00');
});

it('can determine next open hours from working date time', function () {
    $openingHours = OpeningHours::create([
        'monday'  => ['09:00-11:00', '13:00-19:00'],
        'tuesday' => ['10:00-11:00', '14:00-19:00'],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 16:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 10:00:00');
});

it('can determine next open hours from working date time immutable', function () {
    $openingHours = OpeningHours::create([
        'monday'  => ['09:00-11:00', '13:00-19:00'],
        'tuesday' => ['10:00-11:00', '14:00-19:00'],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTimeImmutable('2016-09-26 16:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 10:00:00');
});

it('can determine next close hours from working date time', function () {
    $openingHours = OpeningHours::create([
        'monday'  => ['09:00-11:00', '13:00-19:00'],
        'tuesday' => ['10:00-11:00', '14:00-19:00'],
    ]);

    $nextTimeClose = $openingHours->nextClose(new DateTime('2016-09-26 16:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2016-09-26 19:00:00');
});

it('can determine next close hours from working date time immutable', function () {
    $openingHours = OpeningHours::create([
        'monday'  => ['09:00-11:00', '13:00-19:00'],
        'tuesday' => ['10:00-11:00', '14:00-19:00'],
    ]);

    $nextTimeClose = $openingHours->nextClose(new DateTimeImmutable('2016-09-26 16:00:00'));

    expect($nextTimeClose)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($nextTimeClose->format('Y-m-d H:i:s'))->toBe('2016-09-26 19:00:00');
});

it('can determine next open hours from early morning', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-11:00', '13:00-19:00'],
        'tuesday'    => ['10:00-11:00', '14:00-19:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTime('2016-09-26 04:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 10:00:00');
});

it('can determine next open hours from early morning immutable', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-11:00', '13:00-19:00'],
        'tuesday'    => ['10:00-11:00', '14:00-19:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTimeImmutable('2016-09-26 04:00:00'));

    expect($nextTimeOpen)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($nextTimeOpen->format('Y-m-d H:i:s'))->toBe('2016-09-27 10:00:00');
});

it('can determine next close hours from early morning', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-11:00', '13:00-19:00'],
        'tuesday'    => ['10:00-11:00', '14:00-19:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    $nextClosedTime = $openingHours->nextClose(new DateTime('2016-09-26 04:00:00'));

    expect($nextClosedTime)->toBeInstanceOf(DateTime::class)
        ->and($nextClosedTime->format('Y-m-d H:i:s'))->toBe('2016-09-27 11:00:00');
});

it('can determine next close hours from early morning immutable', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['09:00-11:00', '13:00-19:00'],
        'tuesday'    => ['10:00-11:00', '14:00-19:00'],
        'exceptions' => [
        '2016-09-26' => [],
        ],
    ]);

    $nextClosedTime = $openingHours->nextClose(new DateTimeImmutable('2016-09-26 04:00:00'));

    expect($nextClosedTime)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($nextClosedTime->format('Y-m-d H:i:s'))->toBe('2016-09-27 11:00:00');
});

it('can set the timezone on the openings hours object', function () {
    $openingHours = new OpeningHours('Europe/Amsterdam');
    $openingHours->fill([
        'monday'     => ['09:00-18:00'],
        'exceptions' => [
        '2016-11-14' => ['09:00-13:00'],
        ],
    ]);

    expect($openingHours->isOpenAt(new DateTime('2016-10-10 10:00')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 15:59')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 08:00')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 06:00')))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 06:00', new DateTimeZone('Europe/Amsterdam'))))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 09:00', new DateTimeZone('Europe/Amsterdam'))))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 17:59', new DateTimeZone('Europe/Amsterdam'))))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-11-14 17:59', new DateTimeZone('Europe/Amsterdam'))))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTime('2016-11-14 12:59', new DateTimeZone('Europe/Amsterdam'))))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-11-14 15:59', new DateTimeZone('America/Denver'))))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 09:59', new DateTimeZone('America/Denver'))))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 10:00')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 15:59')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 08:00')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 06:00')))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 06:00', new DateTimeZone('Europe/Amsterdam'))))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 09:00', new DateTimeZone('Europe/Amsterdam'))))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 17:59', new DateTimeZone('Europe/Amsterdam'))))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-11-14 17:59', new DateTimeZone('Europe/Amsterdam'))))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTime('2016-11-14 12:59', new DateTimeZone('Europe/Amsterdam'))))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-11-14 15:59', new DateTimeZone('America/Denver'))))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 09:59', new DateTimeZone('America/Denver'))))->toBeTrue();

    date_default_timezone_set('America/Denver');
    expect($openingHours->isOpenAt(new DateTime('2016-10-10 09:59')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTime('2016-10-10 10:00')))->toBeFalse()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 09:59')))->toBeTrue()
        ->and($openingHours->isOpenAt(new DateTimeImmutable('2016-10-10 10:00')))->toBeFalse();

});

it('can handle timezone for date string', function ($timezone) {
    $openingHours = new OpeningHours($timezone);
    $openingHours->fill([
        'monday'     => ['09:00-18:00'],
    ]);
    expect($openingHours->isOpenOn('2020-10-20'))->toBeFalse()
        ->and($openingHours->isOpenOn('2020-10-19'))->toBeTrue();
})->with('timezones');

it('can set data', function () {
    $openingHours = OpeningHours::create([]);
    $openingHours->setData(['foo' => 'bar']);

    expect($openingHours->getData())->toBe(['foo' => 'bar']);
});

it('can determine that its open now', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['00:00-23:59'],
        'tuesday'   => ['00:00-23:59'],
        'wednesday' => ['00:00-23:59'],
        'thursday'  => ['00:00-23:59'],
        'friday'    => ['00:00-23:59'],
        'saturday'  => ['00:00-23:59'],
        'sunday'    => ['00:00-23:59'],
    ]);

    expect($openingHours->isOpen())->toBeTrue();
});

it('can determine that its closed now', function () {
    $openingHours = OpeningHours::create([]);

    expect($openingHours->isClosed())->toBeTrue();
});

it('can retrieve regular closing days as strings', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['09:00-18:00'],
        'tuesday'   => ['09:00-18:00'],
        'wednesday' => ['09:00-18:00'],
        'thursday'  => ['09:00-18:00'],
        'friday'    => ['09:00-18:00'],
        'saturday'  => [],
        'sunday'    => [],
    ]);

    expect($openingHours->regularClosingDays())->toBe(['saturday', 'sunday']);
});

it('can retrieve regular closing days as iso numbers', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['09:00-18:00'],
        'tuesday'   => ['09:00-18:00'],
        'wednesday' => ['09:00-18:00'],
        'thursday'  => ['09:00-18:00'],
        'friday'    => ['09:00-18:00'],
        'saturday'  => [],
        'sunday'    => [],
    ]);

    expect($openingHours->regularClosingDaysISO())->toBe([6, 7]);
});

it('can retrieve a list of exceptional closing dates', function () {
    $openingHours = OpeningHours::create([
        'exceptions' => [
        '2017-06-01' => [],
        '2017-06-02' => [],
        ],
    ]);

    $exceptionalClosingDates = $openingHours->exceptionalClosingDates();

    expect($exceptionalClosingDates)->toHaveCount(2)
        ->and($exceptionalClosingDates[0]->format('Y-m-d'))->toBe('2017-06-01')
        ->and($exceptionalClosingDates[1]->format('Y-m-d'))->toBe('2017-06-02');
});

it('works when starting at midnight', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['00:00-16:00'],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTime());
    expect($nextTimeOpen)->toBeInstanceOf(DateTime::class);
});

it('works when starting at midnight immutable', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['00:00-16:00'],
    ]);

    $nextTimeOpen = $openingHours->nextOpen(new DateTimeImmutable());
    expect($nextTimeOpen)->toBeInstanceOf(DateTimeImmutable::class);
});

it('can set the timezone', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['00:00-16:00'],
    ]);
    $openingHours->setTimezone('Asia/Taipei');
    $openingHoursForWeek = $openingHours->forWeek();

    expect($openingHoursForWeek)->toHaveCount(7)
        ->and((string)$openingHoursForWeek['monday'][0])->toBe('00:00-16:00')
        ->and($openingHoursForWeek['tuesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['wednesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['thursday'])->toHaveCount(0)
        ->and($openingHoursForWeek['friday'])->toHaveCount(0)
        ->and($openingHoursForWeek['saturday'])->toHaveCount(0)
        ->and($openingHoursForWeek['sunday'])->toHaveCount(0);
});

it('can set the timezone on construct with date time zone', function () {
    $openingHours = new OpeningHours(new DateTimeZone('Asia/Taipei'));
    $openingHours->fill([
        'monday' => ['00:00-16:00'],
    ]);
    $openingHoursForWeek = $openingHours->forWeek();

    expect($openingHoursForWeek)->toHaveCount(7)
        ->and((string)$openingHoursForWeek['monday'][0])->toBe('00:00-16:00')
        ->and($openingHoursForWeek['tuesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['wednesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['thursday'])->toHaveCount(0)
        ->and($openingHoursForWeek['friday'])->toHaveCount(0)
        ->and($openingHoursForWeek['saturday'])->toHaveCount(0)
        ->and($openingHoursForWeek['sunday'])->toHaveCount(0);
});

it('can set the timezone on construct with string', function () {
    $openingHours = new OpeningHours('Asia/Taipei');
    $openingHours->fill([
        'monday' => ['00:00-16:00'],
    ]);
    $openingHoursForWeek = $openingHours->forWeek();

    expect($openingHoursForWeek)->toHaveCount(7)
        ->and((string)$openingHoursForWeek['monday'][0])->toBe('00:00-16:00')
        ->and($openingHoursForWeek['tuesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['wednesday'])->toHaveCount(0)
        ->and($openingHoursForWeek['thursday'])->toHaveCount(0)
        ->and($openingHoursForWeek['friday'])->toHaveCount(0)
        ->and($openingHoursForWeek['saturday'])->toHaveCount(0)
        ->and($openingHoursForWeek['sunday'])->toHaveCount(0);
});

it('throw an exception on invalid timezone', function () {
    new OpeningHours(['foo']);
})->throws(InvalidArgumentException::class, 'Invalid Timezone');

it('throw an exception on limit exceeded void array next open', function () {
    OpeningHours::create([])->nextOpen(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No open date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded void array previous open', function () {
    OpeningHours::create([])->previousOpen(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No open date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded full array next open', function () {
    OpeningHours::create([
        'monday'    => ['00:00-24:00'],
        'tuesday'   => ['00:00-24:00'],
        'wednesday' => ['00:00-24:00'],
        'thursday'  => ['00:00-24:00'],
        'friday'    => ['00:00-24:00'],
        'saturday'  => ['00:00-24:00'],
        'sunday'    => ['00:00-24:00'],
    ])->nextOpen(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No open date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded full array previous open', function () {
    OpeningHours::create([
        'monday'    => ['00:00-24:00'],
        'tuesday'   => ['00:00-24:00'],
        'wednesday' => ['00:00-24:00'],
        'thursday'  => ['00:00-24:00'],
        'friday'    => ['00:00-24:00'],
        'saturday'  => ['00:00-24:00'],
        'sunday'    => ['00:00-24:00'],
    ])->previousOpen(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No open date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded full array next open with exceptions', function () {
    OpeningHours::create([
        'monday'     => ['00:00-24:00'],
        'tuesday'    => ['00:00-24:00'],
        'wednesday'  => ['00:00-24:00'],
        'thursday'   => ['00:00-24:00'],
        'friday'     => ['00:00-24:00'],
        'saturday'   => ['00:00-24:00'],
        'sunday'     => ['00:00-24:00'],
        'exceptions' => [
        '2022-09-05' => [],
        ],
    ])->nextOpen(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No open date/time found in the next 366 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded void array next close', function () {
    OpeningHours::create([])->nextClose(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No close date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded void array previous close', function () {
    OpeningHours::create([])->previousClose(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No close date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded full array next close', function () {
    OpeningHours::create([
        'monday'    => ['00:00-24:00'],
        'tuesday'   => ['00:00-24:00'],
        'wednesday' => ['00:00-24:00'],
        'thursday'  => ['00:00-24:00'],
        'friday'    => ['00:00-24:00'],
        'saturday'  => ['00:00-24:00'],
        'sunday'    => ['00:00-24:00'],
    ])->nextClose(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No close date/time found in the next 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('throw an exception on limit exceeded full array previous close', function () {
    OpeningHours::create([
        'monday'    => ['00:00-24:00'],
        'tuesday'   => ['00:00-24:00'],
        'wednesday' => ['00:00-24:00'],
        'thursday'  => ['00:00-24:00'],
        'friday'    => ['00:00-24:00'],
        'saturday'  => ['00:00-24:00'],
        'sunday'    => ['00:00-24:00'],
    ])->previousClose(new DateTime('2019-06-06 19:02:00'));
})->throws(MaximumLimitExceeded::class, 'No close date/time found in the previous 8 days, use $openingHours->setDayLimit() to increase the limit.');

it('should handle far exception', function () {
    expect(
        OpeningHours::create([
            'monday'     => ['00:00-24:00'],
            'tuesday'    => ['00:00-24:00'],
            'wednesday'  => ['00:00-24:00'],
            'thursday'   => ['00:00-24:00'],
            'friday'     => ['00:00-24:00'],
            'saturday'   => ['00:00-24:00'],
            'sunday'     => ['00:00-24:00'],
            'exceptions' => [
                '12-25' => [],
            ],
        ])->nextClose(new DateTime('2019-06-06 19:02:00'))->format('Y-m-d H:i:s')
    )->toEqual('2019-12-25 00:00:00');
});

it('should handle very far future exception by changing limit', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['00:00-24:00'],
        'tuesday'    => ['00:00-24:00'],
        'wednesday'  => ['00:00-24:00'],
        'thursday'   => ['00:00-24:00'],
        'friday'     => ['00:00-24:00'],
        'saturday'   => ['00:00-24:00'],
        'sunday'     => ['00:00-24:00'],
        'exceptions' => [
        '2022-12-25' => [],
        ],
    ]);
    $openingHours->setDayLimit(3000);

    expect($openingHours->nextClose(new DateTime('2019-06-06 19:02:00'))->format('Y-m-d H:i:s'))->toBe('2022-12-25 00:00:00');
});

it('should handle very far past exception by changing limit', function () {
    $openingHours = OpeningHours::create([
        'monday'     => ['00:00-24:00'],
        'tuesday'    => ['00:00-24:00'],
        'wednesday'  => ['00:00-24:00'],
        'thursday'   => ['00:00-24:00'],
        'friday'     => ['00:00-24:00'],
        'saturday'   => ['00:00-24:00'],
        'sunday'     => ['00:00-24:00'],
        'exceptions' => [
        '2013-12-25' => [],
        ],
    ]);
    $openingHours->setDayLimit(3000);

    expect($openingHours->previousOpen(new DateTime('2019-06-06 19:02:00'))->format('Y-m-d H:i:s'))->toBe('2013-12-26 00:00:00');
});

it('should handle open range', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['10:00-16:00', '19:30-20:30'],
        'tuesday'   => ['22:30-04:00'],
        'wednesday' => ['07:00-10:00'],
        'thursday'  => ['09:00-12:00'],
        'friday'    => ['09:00-12:00'],
        'saturday'  => [],
        'sunday'    => [],
    ]);

    expect($openingHours->currentOpenRange(new DateTime('2019-07-15 08:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-15 17:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-16 22:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-17 04:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-15 11:00:00'))->format())->toBe('10:00-16:00')
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-15 20:00:00'))->format())->toBe('19:30-20:30')
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-16 22:30:00'))->format())->toBe('22:30-04:00')
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-16 22:40:00'))->format())->toBe('22:30-04:00')
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-17 03:59:59'))->format())->toBe('22:30-04:00')
        ->and($openingHours->currentOpenRange(new DateTime('2019-07-17 07:59:59'))->format())->toBe('07:00-10:00');
});

it('should handle open start date time', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['10:00-16:00', '19:30-20:30'],
        'tuesday'   => ['22:30-04:00'],
        'wednesday' => ['07:00-10:00'],
        'thursday'  => ['09:00-12:00'],
        'friday'    => ['09:00-12:00'],
        'saturday'  => [],
        'sunday'    => [],
    ]);

    expect($openingHours->currentOpenRangeStart(new DateTime('2019-07-15 08:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-15 17:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-16 22:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-17 04:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-15 11:00:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-15 10:00:00')
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-15 20:00:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-15 19:30:00')
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-16 22:30:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-16 22:30:00')
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-16 22:40:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-16 22:30:00')
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-17 03:59:59'))->format('Y-m-d H:i:s'))->toBe('2019-07-16 22:30:00')
        ->and($openingHours->currentOpenRangeStart(new DateTime('2019-07-17 07:59:59'))->format('Y-m-d H:i:s'))->toBe('2019-07-17 07:00:00');
});

it('should handle open end date time', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['10:00-16:00', '19:30-20:30'],
        'tuesday'   => ['22:30-04:00'],
        'wednesday' => ['07:00-10:00'],
        'thursday'  => ['09:00-12:00'],
        'friday'    => ['09:00-12:00'],
        'saturday'  => [],
        'sunday'    => [],
    ]);

    expect($openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 08:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 17:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-16 22:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-17 04:00:00')))->toBeFalse()
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 11:00:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-15 16:00:00')
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-15 20:00:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-15 20:30:00')
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-16 22:30:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-17 04:00:00')
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-16 22:40:00'))->format('Y-m-d H:i:s'))->toBe('2019-07-17 04:00:00')
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-17 03:59:59'))->format('Y-m-d H:i:s'))->toBe('2019-07-17 04:00:00')
        ->and($openingHours->currentOpenRangeEnd(new DateTime('2019-07-17 07:59:59'))->format('Y-m-d H:i:s'))->toBe('2019-07-17 10:00:00');
});

it('should support empty arrays with merge', function () {
    $hours = OpeningHours::createAndMergeOverlappingRanges(
        [
        'exceptions' => [
            '01-01' => [
            'hours' => [],
            'data'  => [
                'id' => 'my_id',
            ],
            ],
            '02-02' => [
            'hours' => [],
            'data'  => [
                'id' => 'my_id',
            ],
            ],
        ],
        ]
    );

    expect($hours->isClosedAt(new DateTimeImmutable('2020-01-01')))->toBeTrue();
});

it('can calculate time diff', function () {
    $openingHours = OpeningHours::create([
        'monday'    => ['10:00-16:00', '19:30-20:30'],
        'tuesday'   => ['22:30-04:00'],
        'wednesday' => ['07:00-10:00'],
        'thursday'  => ['09:00-12:00'],
        'friday'    => ['09:00-12:00'],
        'saturday'  => [],
        'sunday'    => [],
    ]);

    expect($openingHours->diffInClosedSeconds(new DateTimeImmutable('Monday 09:59:58'), new DateTimeImmutable('Monday 10:59:58')))->toBe(2.0)
        ->and($openingHours->diffInOpenSeconds(new DateTimeImmutable('Monday 09:59:58'), new DateTimeImmutable('Monday 10:59:58')))->toBe(3600.0 - 2.0);

    if (version_compare(PHP_VERSION, '7.1.0-dev', '>=')) {
        expect($openingHours->diffInClosedSeconds(new DateTimeImmutable('Monday 09:59:58.5'), new DateTimeImmutable('Monday 10:59:58.5')))->toBe(1.5)
            ->and($openingHours->diffInOpenSeconds(new DateTimeImmutable('Monday 09:59:58.5'), new DateTimeImmutable('Monday 10:59:58.5')))->toBe(3600.0 - 1.5);
    }

    expect($openingHours->diffInOpenMinutes(new DateTimeImmutable('Monday 3pm'), new DateTimeImmutable('Monday 8pm')))->toBe(1.5 * 60)
        ->and($openingHours->diffInClosedMinutes(new DateTimeImmutable('Monday 3pm'), new DateTimeImmutable('Monday 8pm')))->toBe(3.5 * 60)
        ->and($openingHours->diffInOpenHours(new DateTimeImmutable('2020-06-21 3pm'), new DateTimeImmutable('2020-06-25 2pm')))->toBe(18.5)
        ->and($openingHours->diffInClosedHours(new DateTimeImmutable('2020-06-21 3pm'), new DateTimeImmutable('2020-06-25 2pm')))->toBe(76.5)
        ->and($openingHours->diffInOpenHours(new DateTimeImmutable('2020-06-25 2pm'), new DateTimeImmutable('2020-06-21 3pm')))->toBe(-18.5)
        ->and($openingHours->diffInClosedHours(new DateTimeImmutable('2020-06-25 2pm'), new DateTimeImmutable('2020-06-21 3pm')))->toBe(-76.5);
});

it('can merge overlapping supports hours and ignore data', function () {
    $data = OpeningHours::mergeOverlappingRanges([
        'monday' => [
            ['hours' => '09:00-21:00', 'data' => 'foobar'],
            ['hours' => '20:00-23:00'],
        ],
        'tuesday' => [[' hours' => '09:00-18:00']],
    ]);

    $monday = OpeningHours::create($data)->forDay('Monday');

    expect($monday->getData())->toBeNull()
        ->and($monday[0]->getData())->toBeNull()
        ->and((string)$monday)->toBe('09:00-23:00');
});

it('can keep hours range', function () {
    $data = OpeningHours::mergeOverlappingRanges([
        'monday' => [
            'hours' => [
                '09:00-12:00',
                '13:00-18:00',
            ],
        ],
    ]);

    $monday = OpeningHours::create($data)->forDay('Monday');

    expect($monday->getData())->toBeNull()
        ->and($monday[0]->getData())->toBeNull()
        ->and((string)$monday)->toEqual('09:00-12:00,13:00-18:00');
});
