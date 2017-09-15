# A helper to query and format a set of opening hours

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/opening-hours.svg?style=flat-square)](https://packagist.org/packages/spatie/opening-hours)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/opening-hours/master.svg?style=flat-square)](https://travis-ci.org/spatie/opening-hours)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/082347b4-a3f6-4b77-b8a1-6a64d50232f7.svg?style=flat-square)](https://insight.sensiolabs.com/projects/082347b4-a3f6-4b77-b8a1-6a64d50232f7)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/opening-hours.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/opening-hours)
[![StyleCI](https://styleci.io/repos/69368104/shield?branch=master)](https://styleci.io/repos/69368104)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/opening-hours.svg?style=flat-square)](https://packagist.org/packages/spatie/opening-hours)

With `spatie/opening-hours` you create an object that describes a business' opening hours, which you can query for `open` or `closed` on days or specific dates, or use to present the times per day.

A set of opening hours is created by passing in a regular schedule, and a list of exceptions.

```php
$openingHours = OpeningHours::create([
    'monday' => ['09:00-12:00', '13:00-18:00'],
    'tuesday' => ['09:00-12:00', '13:00-18:00'],
    'wednesday' => ['09:00-12:00'],
    'thursday' => ['09:00-12:00', '13:00-18:00'],
    'friday' => ['09:00-12:00', '13:00-20:00'],
    'saturday' => ['09:00-12:00', '13:00-16:00'],
    'sunday' => [],
    'exceptions' => [
        '2016-11-11' => ['09:00-12:00'],
        '2016-12-25' => [],
        '01-01' => [], // Recurring on each 1st of january
        '12-25' => ['09:00-12:00'], // Recurring on each 25nd of december
    ],
]);
```

The object can be queried for a day in the week, which will return a result based on the regular schedule:

```php
// Open on Mondays:
$openingHours->isOpenOn('monday'); // true

// Closed on Sundays:
$openingHours->isOpenOn('sunday'); // false
```

It can also be queried for a specific date and time:

```php
// Closed because it's after hours:
$openingHours->isOpenAt(new DateTime('2016-09-26 19:00:00')); // false

// Closed because Christmas was set as an exception
$openingHours->isOpenAt(new DateTime('2016-12-25')); // false
```

It can also return arrays of opening hours for a week or a day:

```php
// OpeningHoursForDay object for the regular schedule
$openingHours->forDay('monday');

// OpeningHoursForDay[] for the regular schedule, keyed by day name
$openingHours->forWeek();

// OpeningHoursForDay object for a specific day
$openingHours->forDate(new DateTime('2016-12-25'));

// OpeningHoursForDay[] of all exceptions, keyed by date
$openingHours->exceptions();
```

It can also return next open `DateTime` from the given `DateTime`.

```
// 2016-12-26 09:00:00
$nextOpen = $openingHours->nextOpen(new DateTime('2016-12-25 10:00:00'));

// 2016-12-24 13:00:00
$nextOpen = $openingHours->nextOpen(new DateTime('2016-12-24 11:00:00'));
```
Read the usage section for the full api.

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment you are required to send us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

The best postcards will get published on the open source page on our website.

## Installation

You can install the package via composer:

``` bash
composer require spatie/opening-hours
```

## Usage

The package should only be used through the `OpeningHours` class. There are also three value object classes used throughout, `Time`, which represents a single time, `TimeRange`, which represents a period with a start and an end, and `openingHoursForDay`, which represents a set of `TimeRange`s which can't overlap.

### `Spatie\OpeningHours\OpeningHours`

#### `OpeningHours::create(array $data): Spatie\OpeningHours\OpeningHours`

Static factory method to fill the set of opening hours.

``` php
$openingHours = OpeningHours::create([
    'monday' => ['09:00-12:00', '13:00-18:00'],
    // ...
]);
```

Not all days are mandatory, if a day is missing, it will be set as closed.

#### `OpeningHours::fill(array $data): Spatie\OpeningHours\OpeningHours`

The same as `create`, but non-static.

``` php
$openingHours = (new OpeningHours)->fill([
    'monday' => ['09:00-12:00', '13:00-18:00'],
    // ...
]);
```

#### `OpeningHours::forWeek(): Spatie\OpeningHours\OpeningHoursForDay[]`

Returns an array of `OpeningHoursForDay` objects for a regular week.

```php
$openingHours->forWeek();
```

#### `OpeningHours::forDay(string $day): Spatie\OpeningHours\OpeningHoursForDay`

Returns an `OpeningHoursForDay` object for a regular day. A day is lowercase string of the english day name.

```php
$openingHours->forDay('monday');
```

#### `OpeningHours::forDate(DateTime $dateTime): Spatie\OpeningHours\OpeningHoursForDay`

Returns an `OpeningHoursForDay` object for a specific date. It looks for an exception on that day, and otherwise it returns the opening hours based on the regular schedule.

```php
$openingHours->forDate(new DateTime('2016-12-25'));
```

#### `OpeningHours::exceptions(): Spatie\OpeningHours\OpeningHoursForDay[]`

Returns an array of all `OpeningHoursForDay` objects for exceptions, keyed by a `Y-m-d` date string.

```php
$openingHours->exceptions();
```

#### `OpeningHours::isOpenOn(string $day): bool`

Checks if the business is op on a day in the regular schedule.

```php
$openingHours->isOpenOn('saturday');
```

#### `OpeningHours::isClosedOn(string $day): bool`

Checks if the business is closed on a day in the regular schedule.

```php
$openingHours->isClosedOn('sunday');
```

#### `OpeningHours::isOpenAt(DateTime $dateTime): bool`

Checks if the business is open on a specific day, at a specific time.

```php
$openingHours->isOpenAt(new DateTime('2016-26-09 20:00'));
```

#### `OpeningHours::isClosedAt(DateTime $dateTime): bool`

Checks if the business is closed on a specific day, at a specific time.

```php
$openingHours->isClosedAt(new DateTime('2016-26-09 20:00'));
```

#### `OpeningHours::isOpen(): bool`

Checks if the business is open right now.

```php
$openingHours->isOpen();
```

#### `OpeningHours::isClosed(): bool`

Checks if the business is closed right now.

```php
$openingHours->isClosed();
```

#### `nextOpen(DateTimeInterface $dateTime) : DateTime`

Returns next open DateTime from the given DateTime

```php
$openingHours->nextOpen(new DateTime('2016-12-24 11:00:00'));
```

### `Spatie\OpeningHours\OpeningHoursForDay`

This class is meant as read-only. It implements `ArrayAccess`, `Countable` and `IteratorAggregate` so you can process the list of `TimeRange`s in an array-like way.

### `Spatie\OpeningHours\TimeRange`

Value object describing a period with a start and an end time. Can be casted to a string in a `H:i-H:i` format.

### `Spatie\OpeningHours\Time`

Value object describing a single time. Can be casted to a string in a `H:i` format.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Sebastian De Deyne](https://github.com/sebastiandedeyne)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
