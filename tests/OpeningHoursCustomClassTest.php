<?php

use Spatie\OpeningHours\Exceptions\InvalidDateTimeClass;
use Spatie\OpeningHours\OpeningHours;

it('can use immutable date time', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'dateTimeClass' => DateTimeImmutable::class,
    ]);

    $date = $openingHours->nextOpen(new DateTimeImmutable('2021-10-11 04:30'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s'))->toBe('2021-10-11 09:00:00');
});

it('can use timezones', function () {
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ]);

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-07-25 09:00:00 UTC');

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 Europe/Oslo'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-07-25 09:00:00 Europe/Oslo');

    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'timezone' => 'Europe/Oslo',
    ]);

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-07-25 07:00:00 UTC');

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-08-01 07:00:00 UTC');

    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ], new DateTimeZone('Europe/Oslo'));
    $openingHours->setOutputTimezone('Europe/Oslo');

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-07-25 09:00:00 Europe/Oslo');

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-08-01 09:00:00 Europe/Oslo');

    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'timezone' => [
        'input' => 'Europe/Oslo',
        'output' => 'UTC',
        ],
    ]);

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-07-25 07:00:00 UTC');

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-08-01 07:00:00 UTC');
    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
    ], 'Europe/Oslo', 'America/New_York');

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 06:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-07-25 03:00:00 America/New_York');

    $date = $openingHours->nextOpen(new DateTimeImmutable('2022-07-25 07:30 UTC'));

    expect($date)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($date->format('Y-m-d H:i:s e'))->toBe('2022-08-01 03:00:00 America/New_York');
});

it('can use mocked time', function () {
    $mock1 = new class extends DateTimeImmutable
    {
        public function __construct($datetime = 'now', DateTimeZone $timezone = null)
        {
        parent::__construct('2021-10-11 04:30', $timezone);
        }
    };
    $mock2 = new class extends DateTimeImmutable
    {
        public function __construct($datetime = 'now', DateTimeZone $timezone = null)
        {
        parent::__construct('2021-10-11 09:30', $timezone);
        }
    };

    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'dateTimeClass' => get_class($mock1),
    ]);

    expect($openingHours->isOpen())->toBeFalse();

    $openingHours = OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'dateTimeClass' => get_class($mock2),
    ]);

    expect($openingHours->isOpen())->toBeTrue();
});

it('should refuse invalid date time class', function () {
    OpeningHours::create([
        'monday' => ['09:00-18:00'],
        'dateTimeClass' => DateTimeZone::class,
    ]);
})->throws(InvalidDateTimeClass::class);
