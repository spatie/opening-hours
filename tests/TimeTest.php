<?php

use Spatie\OpeningHours\Exceptions\InvalidTimeString;
use Spatie\OpeningHours\Time;

it('can be created from a string', function () {
    expect((string)Time::fromString('00:00'))->toBe('00:00')
        ->and((string)Time::fromString('16:32'))->toBe('16:32')
        ->and((string)Time::fromString('24:00'))->toBe('24:00');
});

it('cant be created from an invalid string', function () {
    Time::fromString('aa:bb');
})->throws(InvalidTimeString::class);

it('cant be created from an invalid hour', function () {
    Time::fromString('26:00');
})->throws(InvalidTimeString::class);

it('cant be created from an out of bound hour', function () {
    Time::fromString('24:01');
})->throws(InvalidTimeString::class);

it('cant be created from an invalid minute', function () {
    Time::fromString('14:60');
})->throws(InvalidTimeString::class);

it('can be created from a date time instance', function () {
    $dateTime = new DateTime('2016-09-27 16:00:00');

    expect((string) Time::fromDateTime($dateTime))->toBe('16:00');

    $dateTime = new DateTimeImmutable('2016-09-27 16:00:00');

    expect((string) Time::fromDateTime($dateTime))->toBe('16:00');
});

it('can determine that its the same as another time', function () {
    expect(Time::fromString('09:00')->isSame(Time::fromString('09:00')))->toBeTrue()
        ->and(Time::fromString('09:00')->isSame(Time::fromString('10:00')))->toBeFalse()
        ->and(Time::fromString('09:00')->isSame(Time::fromString('09:30')))->toBeFalse();
});

it('can determine that its before another time', function () {
    expect(Time::fromString('09:00')->isBefore(Time::fromString('10:00')))->toBeTrue()
        ->and(Time::fromString('09:00')->isBefore(Time::fromString('09:30')))->toBeTrue()
        ->and(Time::fromString('09:00')->isBefore(Time::fromString('09:00')))->toBeFalse()
        ->and(Time::fromString('09:00')->isBefore(Time::fromString('08:00')))->toBeFalse()
        ->and(Time::fromString('09:00')->isBefore(Time::fromString('08:30')))->toBeFalse()
        ->and(Time::fromString('08:30')->isBefore(Time::fromString('08:00')))->toBeFalse();
});

it('can determine that its after another time', function () {
    expect(Time::fromString('09:00')->isAfter(Time::fromString('08:00')))->toBeTrue()
        ->and(Time::fromString('09:30')->isAfter(Time::fromString('09:00')))->toBeTrue()
        ->and(Time::fromString('09:00')->isAfter(Time::fromString('08:30')))->toBeTrue()
        ->and(Time::fromString('09:00')->isAfter(Time::fromString('09:00')))->toBeFalse()
        ->and(Time::fromString('09:00')->isAfter(Time::fromString('09:30')))->toBeFalse()
        ->and(Time::fromString('09:00')->isAfter(Time::fromString('10:00')))->toBeFalse();
});

it('can determine that its the same or after another time', function () {
    expect(Time::fromString('09:00')->isSameOrAfter(Time::fromString('08:00')))->toBeTrue()
        ->and(Time::fromString('09:00')->isSameOrAfter(Time::fromString('09:00')))->toBeTrue()
        ->and(Time::fromString('09:30')->isSameOrAfter(Time::fromString('09:30')))->toBeTrue()
        ->and(Time::fromString('09:30')->isSameOrAfter(Time::fromString('09:00')))->toBeTrue()
        ->and(Time::fromString('09:00')->isSameOrAfter(Time::fromString('10:00')))->toBeFalse();
});

it('can accept any date format with the date time interface', function () {
    $dateTime = date_create_immutable('2012-11-06 13:25:59.123456');

    expect((string) Time::fromDateTime($dateTime))->toBe('13:25');
});

it('can be formatted', function () {
    expect(Time::fromString('09:00')->format())->toBe('09:00')
        ->and(Time::fromString('09:00')->format('H:i'))->toBe('09:00')
        ->and(Time::fromString('09:00')->format('g A'))->toBe('9 AM');
});

it('can get hours and minutes', function () {
    $time = Time::fromString('16:30');
    expect($time->hours())->toBe(16)
        ->and($time->minutes())->toBe(30);
});

it('can calculate diff', function () {
    $time1 = Time::fromString('16:30');
    $time2 = Time::fromString('16:05');
    expect($time1->diff($time2)->h)->toBe(0)
        ->and($time1->diff($time2)->i)->toBe(25);
});

it('should not mutate passed datetime', function () {
    $dateTime = new DateTime('2016-09-27 12:00:00');
    $time = Time::fromString('15:00');
    expect($time->toDateTime($dateTime)->format('Y-m-d H:i:s'))->toBe('2016-09-27 15:00:00')
        ->and($dateTime->format('Y-m-d H:i:s'))->toBe('2016-09-27 12:00:00');
});

it('should not mutate passed datetime immutable', function () {
    $dateTime = new DateTimeImmutable('2016-09-27 12:00:00');
    $time = Time::fromString('15:00');
    expect($time->toDateTime($dateTime)->format('Y-m-d H:i:s'))->toBe('2016-09-27 15:00:00')
        ->and($dateTime->format('Y-m-d H:i:s'))->toBe('2016-09-27 12:00:00');
});
