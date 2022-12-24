<?php

use Spatie\OpeningHours\PreciseTime;

it('can be formatted', function () {
    $date = new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto');
    expect(
        PreciseTime::fromDateTime($date)->format('Y-m-d H:i:s.u e', 'Europe/Berlin')
    )->toEqual('2022-08-08 05:32:58.123456 Europe/Berlin')
        ->and(
            PreciseTime::fromDateTime($date)->format('Y-m-d H:i:s.u e', new DateTimeZone('Asia/Tokyo'))
        )->toEqual('2022-08-08 12:32:58.123456 Asia/Tokyo');
});

it('can return original datetime', function () {
    $date = new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto');
    expect(PreciseTime::fromDateTime($date)->toDateTime())->toBe($date)
        ->and(
            PreciseTime::fromDateTime($date)->toDateTime(
                new DateTimeImmutable('2021-11-25 15:02:03.987654 Asia/Tokyo')
            )->format('Y-m-d H:i:s' . (PHP_VERSION < 7.1 ? '' : '.u') . ' e')
        )->toEqual('2021-11-25 23:32:58' . (PHP_VERSION < 7.1 ? '' : '.123456') . ' Asia/Tokyo');
});

it('can return diff', function () {
    $date = new DateTimeImmutable('2021-08-07 23:32:58.123456 America/Toronto');
    expect(
        PreciseTime::fromDateTime($date)->diff(PreciseTime::fromDateTime(
            new DateTimeImmutable('2022-11-25 15:02:03.987654 Asia/Tokyo')
        ))->format('%H %I %S %F')
    )->toEqual('02 29 05 '.(PHP_VERSION < 7.1 ? '%F' : '864198'));
});

it('can be compared', function () {
    $date = new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto');
    expect(PreciseTime::fromDateTime($date)->toDateTime())->toEqual($date)
        ->and(
            PreciseTime::fromDateTime(
                new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto')
            )->isSame(PreciseTime::fromDateTime(
                new DateTimeImmutable('2021-11-25 23:32:58.123456 Asia/Tokyo')
            ))
        )->toBeTrue()
        ->and(
            PreciseTime::fromDateTime(
                new DateTimeImmutable('2022-08-07 23:32:58.123456 America/Toronto')
            )->isSame(PreciseTime::fromDateTime(
                new DateTimeImmutable('2022-08-07 23:32:58.123457 America/Toronto')
            ))
        )->toBeFalse();
});

it('can output hours and minutes', function () {
    $date = PreciseTime::fromString('2022-08-07 23:32:58.123456 America/Toronto');
    expect($date->hours())->toBe(23)
        ->and($date->minutes())->toBe(32);
});
