<?php

use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\TimeRange;

it('fills opening hours with overflow', function () {
    $openingHours = OpeningHours::create([
        'overflow' => true,
        'monday' => ['09:00-02:00'],
    ], null);

    expect($openingHours->forDay('monday')[0])->toBeInstanceOf(TimeRange::class)
        ->and('09:00-02:00')->toBe((string)$openingHours->forDay('monday')[0]);
});

it('check open with overflow', function () {
    $openingHours = OpeningHours::create([
        'overflow' => true,
        'monday' => ['09:00-02:00'],
        'tuesday' => ['19:00-04:00'],
        'wednesday' => ['09:00-02:00'],
    ], null);

    $shouldBeOpen = new DateTime('2019-04-23 01:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue();

    $shouldBeOpen = new DateTime('2019-04-23 03:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeFalse();

    $shouldBeOpen = new DateTime('2019-04-23 18:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeFalse();

    $shouldBeOpen = new DateTime('2019-04-23 20:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue();

    $shouldBeOpen = new DateTime('2019-04-23 23:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue();

    $shouldBeOpen = new DateTime('2019-04-24 02:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue();

    $shouldBeOpen = new DateTime('2019-04-24 03:59:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue();

    $shouldBeOpen = new DateTime('2019-04-24 04:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeFalse();

    $shouldBeOpen = new DateTime('2019-04-24 09:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue();
});

it('check open with overflow immutable', function () {
    $openingHours = OpeningHours::create([
        'overflow' => true,
        'monday' => ['09:00-02:00'],
    ], null);

    $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
    expect($openingHours->isOpenAt($shouldBeOpen))->toBeTrue();
});

it('next close with overflow', function () {
    $openingHours = OpeningHours::create([
        'overflow' => true,
        'monday' => ['09:00-02:00'],
    ], null);

    $shouldBeOpen = new DateTime('2019-04-23 01:00:00');
    expect($openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s'))->toBe('2019-04-23 02:00:00');
});

it('next close with overflow immutable', function () {
    $openingHours = OpeningHours::create([
        'overflow' => true,
        'monday' => ['09:00-02:00'], // 2019-04-22
    ], null);

    $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
    $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($nextTimeClosed)->toBe('2019-04-23 02:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:00:00');
    $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($nextTimeClosed)->toBe('2019-04-23 02:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-22 07:00:00');
    $nextTimeClosed = $openingHours->nextClose($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($nextTimeClosed)->toBe('2019-04-23 02:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
    $nextTimeClosed = $openingHours->nextOpen($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($nextTimeClosed)->toBe('2019-04-29 09:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:00:00');
    $nextTimeClosed = $openingHours->nextOpen($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($nextTimeClosed)->toBe('2019-04-29 09:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-22 07:00:00');
    $nextTimeClosed = $openingHours->nextOpen($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($nextTimeClosed)->toBe('2019-04-22 09:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:30:00');
    $previousTimeOpen = $openingHours->previousOpen($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($previousTimeOpen)->toBe('2019-04-22 09:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
    $previousTimeOpen = $openingHours->previousOpen($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($previousTimeOpen)->toBe('2019-04-22 09:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-23 05:00:00');
    $previousTimeOpen = $openingHours->previousOpen($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($previousTimeOpen)->toBe('2019-04-22 09:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-22 23:30:00');
    $previousTimeOpen = $openingHours->previousClose($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($previousTimeOpen)->toBe('2019-04-22 02:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-23 01:00:00');
    $previousTimeOpen = $openingHours->previousClose($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($previousTimeOpen)->toBe('2019-04-22 02:00:00');

    $shouldBeOpen = new DateTimeImmutable('2019-04-23 05:00:00');
    $previousTimeOpen = $openingHours->previousClose($shouldBeOpen)->format('Y-m-d H:i:s');
    expect($previousTimeOpen)->toBe('2019-04-22 02:00:00');
});

it('overflow on simple ranges', function () {
    //Tuesday 4th of June 2019, 11.35 am
    $time = new DateTime('2019-06-04 11:35:00');

    $openWithOverflow = OpeningHours::create([
        'overflow' => true,
        'monday' => ['11:00-18:00'],
        'tuesday' => ['13:37-15:37'],
    ]);

    $openWithoutOverflow = OpeningHours::create([
        'overflow' => false,
        'monday' => ['11:00-18:00'],
        'tuesday' => ['13:37-15:37'],
    ]);

    expect($openWithOverflow->isOpenAt($time))->toBeFalse()
        ->and($openWithoutOverflow->isOpenAt($time))->toBeFalse();
});
