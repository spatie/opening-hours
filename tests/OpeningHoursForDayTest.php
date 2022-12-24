<?php

use Spatie\OpeningHours\Exceptions\NonMutableOffsets;
use Spatie\OpeningHours\Exceptions\OverlappingTimeRanges;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

it('can be created from an array of time range strings', function () {
    $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

    expect($openingHoursForDay)->toHaveCount(2)
        ->and($openingHoursForDay[0])->toBeInstanceOf(TimeRange::class)
        ->and((string)$openingHoursForDay[0])->toBe('09:00-12:00')
        ->and($openingHoursForDay[1])->toBeInstanceOf(TimeRange::class)
        ->and((string)$openingHoursForDay[1])->toBe('13:00-18:00');

});

it('cant be created when time ranges overlap', function () {
    OpeningHoursForDay::fromStrings(['09:00-18:00', '14:00-20:00']);
})->throws(OverlappingTimeRanges::class);

it('can determine whether its open at a time', function () {
    $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-18:00']);

    expect($openingHoursForDay->isOpenAt(Time::fromString('09:00')))->toBeTrue()
        ->and($openingHoursForDay->isOpenAt(Time::fromString('08:00')))->toBeFalse()
        ->and($openingHoursForDay->isOpenAt(Time::fromString('18:00')))->toBeFalse();
});

it('casts to string', function () {
    $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

    expect((string) $openingHoursForDay)->toEqual('09:00-12:00,13:00-18:00');
});

it('can offset is existed', function () {
    $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

    expect($openingHoursForDay->offsetExists(0))->toBeTrue()
        ->and($openingHoursForDay->offsetExists(1))->toBeTrue()
        ->and($openingHoursForDay->offsetExists(2))->toBeFalse();
});

it('can unset offset', function () {
    $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

    expect($openingHoursForDay->offsetUnset(0))->toBeNull()
        ->and($openingHoursForDay->offsetUnset(1))->toBeNull()
        ->and($openingHoursForDay->offsetUnset(2))->toBeNull();
});

it('can get iterator', function () {
    $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

    expect($openingHoursForDay->getIterator()->getArrayCopy())->toHaveCount(2);
});

it('cant set iterator item', function () {
    $openingHoursForDay = OpeningHoursForDay::fromStrings(['09:00-12:00', '13:00-18:00']);

    $openingHoursForDay[0] = TimeRange::fromString('07:00-11:00');
})->throws(NonMutableOffsets::class);
