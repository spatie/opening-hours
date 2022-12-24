<?php

use Spatie\OpeningHours\Exceptions\InvalidTimeRangeArray;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeList;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

it('can be created from a string', function () {
    expect((string) TimeRange::fromString('16:00-18:00'))->toBe('16:00-18:00');
});

it('cant be created from an invalid range', function () {
    TimeRange::fromString('16:00/18:00');
})->throws(InvalidTimeRangeString::class);

it('will throw an exception when passing a invalid array', function () {
    TimeRange::fromArray([]);
})->throws(InvalidTimeRangeArray::class);

it('will throw an exception when passing a empty array to list', function () {
    TimeRange::fromList([]);
})->throws(InvalidTimeRangeList::class);

it('will throw an exception when passing a invalid array to list', function () {
    TimeRange::fromList([
        'foo',
    ]);
})->throws(InvalidTimeRangeList::class);

it('can get the time objects', function () {
    $timeRange = TimeRange::fromString('16:00-18:00');

    expect($timeRange->start())->toBeInstanceOf(Time::class)
        ->and($timeRange->end())->toBeInstanceOf(Time::class);
});

it('can determine that it spills over to the next day', function () {
    expect(TimeRange::fromString('18:00-01:00')->spillsOverToNextDay())->toBeTrue()
        ->and(TimeRange::fromString('18:00-23:00')->spillsOverToNextDay())->toBeFalse();
});

it('can determine that it contains a time', function () {
    expect(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('16:00')))->toBeTrue()
        ->and(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('17:00')))->toBeTrue()
        ->and(TimeRange::fromString('16:00-18:00')->containsTime(Time::fromString('18:00')))->toBeFalse()
        ->and(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('00:30')))->toBeFalse()
        ->and(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('00:30')))->toBeTrue()
        ->and(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('22:00')))->toBeTrue()
        ->and(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('17:00')))->toBeFalse()
        ->and(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('02:00')))->toBeFalse()
        ->and(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('02:00')))->toBeFalse()
        ->and(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('18:00')))->toBeTrue()
        ->and(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('00:59')))->toBeFalse()
        ->and(TimeRange::fromString('18:00-01:00')->containsTime(Time::fromString('01:00')))->toBeFalse()
        ->and(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('00:59')))->toBeTrue()
        ->and(TimeRange::fromMidnight(Time::fromString('01:00'))->containsTime(Time::fromString('01:00')))->toBeFalse();

});

it('can determine that it contains a time over midnight', function () {
    expect(TimeRange::fromString('10:00-18:00')->containsNightTime(Time::fromString('17:00')))->toBeFalse()
        ->and(TimeRange::fromString('18:00-10:00')->containsNightTime(Time::fromString('17:00')))->toBeFalse()
        ->and(TimeRange::fromString('10:00-18:00')->containsNightTime(Time::fromString('08:00')))->toBeFalse()
        ->and(TimeRange::fromString('18:00-10:00')->containsNightTime(Time::fromString('08:00')))->toBeTrue();
});

it('can determine that it overlaps another time range', function () {
    expect(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('15:00-17:00')))->toBeTrue()
        ->and(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('17:00-19:00')))->toBeTrue()
        ->and(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('17:00-17:30')))->toBeTrue()
        ->and(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('21:00-23:00')))->toBeTrue()
        ->and(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('01:00-02:00')))->toBeFalse()
        ->and(TimeRange::fromString('22:00-02:00')->overlaps(TimeRange::fromString('23:00-23:30')))->toBeTrue()
        ->and(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('14:00-15:00')))->toBeFalse()
        ->and(TimeRange::fromString('16:00-18:00')->overlaps(TimeRange::fromString('19:00-20:00')))->toBeFalse();

});

it('can be formatted', function () {
    expect(TimeRange::fromString('16:00-18:00')->format())->toBe('16:00-18:00')
        ->and(
            TimeRange::fromString('16:00-18:00')->format('H:i', '%s - %s')
        )->toEqual('16:00 - 18:00')
        ->and(
            TimeRange::fromString('16:00-18:00')->format('g A', 'from %s to %s')
        )->toEqual('from 4 PM to 6 PM');
});
