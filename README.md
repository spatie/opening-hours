# A helper to query and format a set of opening hours

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/opening-hours.svg?style=flat-square)](https://packagist.org/packages/spatie/opening-hours)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Tests](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Factions-badge.atrox.dev%2Fspatie%2Fopening-hours%2Fbadge&style=flat-square&label=Build&logo=none)](https://actions-badge.atrox.dev/spatie/opening-hours/goto)
[![Coverage](https://img.shields.io/codecov/c/github/spatie/opening-hours.svg?style=flat-square)](https://codecov.io/github/spatie/opening-hours?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/opening-hours.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/opening-hours)
[![StyleCI](https://styleci.io/repos/69368104/shield?branch=master)](https://styleci.io/repos/69368104)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/opening-hours.svg?style=flat-square)](https://packagist.org/packages/spatie/opening-hours)

With `spatie/opening-hours` you create an object that describes a business' opening hours, which you can query for `open` or `closed` on days or specific dates, or use to present the times per day.

`spatie/opening-hours` can be used directly on [Carbon](https://carbon.nesbot.com/) thanks
to [cmixin/business-time](https://github.com/kylekatarnls/business-time) so you can benefit
opening hours features directly on your enhanced date objects.

A set of opening hours is created by passing in a regular schedule, and a list of exceptions.

```php
// Add the use at the top of each file where you want to use the OpeningHours class:
use Spatie\OpeningHours\OpeningHours;

$openingHours = OpeningHours::create([
    'monday'     => ['09:00-12:00', '13:00-18:00'],
    'tuesday'    => ['09:00-12:00', '13:00-18:00'],
    'wednesday'  => ['09:00-12:00'],
    'thursday'   => ['09:00-12:00', '13:00-18:00'],
    'friday'     => ['09:00-12:00', '13:00-20:00'],
    'saturday'   => ['09:00-12:00', '13:00-16:00'],
    'sunday'     => [],
    'exceptions' => [
        '2016-11-11' => ['09:00-12:00'],
        '2016-12-25' => [],
        '01-01'      => [],                // Recurring on each 1st of January
        '12-25'      => ['09:00-12:00'],   // Recurring on each 25th of December
    ],
]);

// This will allow you to display things like:

$now = new DateTime('now');
$range = $openingHours->currentOpenRange($now);

if ($range) {
    echo "It's open since ".$range->start()."\n";
    echo "It will close at ".$range->end()."\n";
} else {
    echo "It's closed since ".$openingHours->previousClose($now)->format('l H:i')."\n";
    echo "It will re-open at ".$openingHours->nextOpen($now)->format('l H:i')."\n";
}
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
$openingHours->isOpenOn('2016-12-25'); // false
```

It can also return arrays of opening hours for a week or a day:

```php
// OpeningHoursForDay object for the regular schedule
$openingHours->forDay('monday');

// OpeningHoursForDay[] for the regular schedule, keyed by day name
$openingHours->forWeek();

// Array of day with same schedule for the regular schedule, keyed by day name, days combined by working hours
$openingHours->forWeekCombined();

// OpeningHoursForDay object for a specific day
$openingHours->forDate(new DateTime('2016-12-25'));

// OpeningHoursForDay[] of all exceptions, keyed by date
$openingHours->exceptions();
```

On construction you can set a flag for overflowing times across days. For example, for a night club opens until 3am on Friday and Saturday:

```php
$openingHours = \Spatie\OpeningHours\OpeningHours::create([
    'overflow' => true,
    'friday'   => ['20:00-03:00'],
    'saturday' => ['20:00-03:00'],
], null);
```

This allows the API to further at yesterdays data to check if the opening hours are open from yesterdays time range. 

You can add data in definitions then retrieve them:

```php
$openingHours = OpeningHours::create([
    'monday' => [
        'data' => 'Typical Monday',
        '09:00-12:00',
        '13:00-18:00',
    ],
    'tuesday' => [
        '09:00-12:00',
        '13:00-18:00',
        [
            '19:00-21:00',
            'data' => 'Extra on Tuesday evening',
        ],
    ],
    'exceptions' => [
        '2016-12-25' => [
            'data' => 'Closed for Christmas',
        ],
    ],
]);

echo $openingHours->forDay('monday')->getData(); // Typical Monday
echo $openingHours->forDate(new DateTime('2016-12-25'))->getData(); // Closed for Christmas
echo $openingHours->forDay('tuesday')[2]->getData(); // Extra on Tuesday evening
```

In the example above, data are strings but it can be any kind of value. So you can embed multiple properties in an array.

For structure convenience, the data-hours couple can be a fully-associative array, so the example above is strictly equivalent to the following:

```php
$openingHours = OpeningHours::create([
    'monday' => [
        'hours' => [
            '09:00-12:00',
            '13:00-18:00',
        ],
        'data' => 'Typical Monday',
    ],
    'tuesday' => [
        ['hours' => '09:00-12:00'],
        ['hours' => '13:00-18:00'],
        ['hours' => '19:00-21:00', 'data' => 'Extra on Tuesday evening'],
    ],
    // Open by night from Wednesday 22h to Thursday 7h:
    'wednesday' => ['22:00-24:00'], // use the special "24:00" to reach midnight included
    'thursday' => ['00:00-07:00'],
    'exceptions' => [
        '2016-12-25' => [
            'hours' => [],
            'data'  => 'Closed for Christmas',
        ],
    ],
]);
```

The last structure tool is the filter, it allows you to pass closures (or callable function/method reference) that take a date as a parameter and returns the settings for the given date.

```php
$openingHours = OpeningHours::create([
    'monday' => [
       '09:00-12:00',
    ],
    'filters' => [
        function ($date) {
            $year         = intval($date->format('Y'));
            $easterMonday = new DateTimeImmutable('2018-03-21 +'.(easter_days($year) + 1).'days');
            if ($date->format('m-d') === $easterMonday->format('m-d')) {
                return []; // Closed on Easter Monday
                // Any valid exception-array can be returned here (range of hours, with or without data)
            }
            // Else the filter does not apply to the given date
        },
    ],
]);
```

If a callable is found in the `"exceptions"` property, it will be added automatically to filters so you can mix filters and exceptions both in the **exceptions** array. The first filter that returns a non-null value will have precedence over the next filters and the **filters** array has precedence over the filters inside the **exceptions** array.

Warning: We will loop on all filters for each date from which we need to retrieve opening hours and can neither predicate nor cache the result (can be a random function) so you must be careful with filters, too many filters or long process inside filters can have a significant impact on the performance.

It can also return the next open or close `DateTime` from a given `DateTime`.

```php
// The next open datetime is tomorrow morning, because we’re closed on 25th of December.
$nextOpen = $openingHours->nextOpen(new DateTime('2016-12-25 10:00:00')); // 2016-12-26 09:00:00

// The next open datetime is this afternoon, after the lunch break.
$nextOpen = $openingHours->nextOpen(new DateTime('2016-12-24 11:00:00')); // 2016-12-24 13:00:00


// The next close datetime is at noon.
$nextClose = $openingHours->nextClose(new DateTime('2016-12-24 10:00:00')); // 2016-12-24 12:00:00

// The next close datetime is tomorrow at noon, because we’re closed on 25th of December.
$nextClose = $openingHours->nextClose(new DateTime('2016-12-25 15:00:00')); // 2016-12-26 12:00:00
```

Read the usage section for the full api.

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/opening-hours.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/opening-hours)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

``` bash
composer require spatie/opening-hours
```

## Usage

The package should only be used through the `OpeningHours` class. There are also three value object classes used throughout, `Time`, which represents a single time, `TimeRange`, which represents a period with a start and an end, and `openingHoursForDay`, which represents a set of `TimeRange`s which can't overlap.

### `Spatie\OpeningHours\OpeningHours`

#### `OpeningHours::create(array $data, $timezone = null): Spatie\OpeningHours\OpeningHours`

Static factory method to fill the set of opening hours.

``` php
$openingHours = OpeningHours::create([
    'monday' => ['09:00-12:00', '13:00-18:00'],
    // ...
]);
```

#### `OpeningHours::mergeOverlappingRanges(array $schedule) : array`

For safety sake, creating `OpeningHours` object with overlapping ranges will throw an exception unless you pass explicitly `'overflow' => true,` in the opening hours array definition. You can also explicitly merge them.

``` php
$ranges = [
  'monday' => ['08:00-11:00', '10:00-12:00'],
];
$mergedRanges = OpeningHours::mergeOverlappingRanges($ranges); // Monday becomes ['08:00-12:00']

OpeningHours::create($mergedRanges);
// Or use the following shortcut to create from ranges that possibly overlap:
OpeningHours::createAndMergeOverlappingRanges($ranges);
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

#### `OpeningHours::forWeekCombined(): array`

Returns an array of days. Array key is first day with same hours, array values are days that have the same working hours and `OpeningHoursForDay` object.

```php
$openingHours->forWeekCombined();
```

#### `OpeningHours::forWeekConsecutiveDays(): array`

Returns an array of concatenated days, adjacent days with the same hours. Array key is first day with same hours, array values are days that have the same working hours and `OpeningHoursForDay` object.

*Warning*: consecutive days are considered from Monday to Sunday without looping (Monday is not consecutive to Sunday) no matter the days order in initial data.

```php
$openingHours->forWeekConsecutiveDays();
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

Checks if the business is open (contains at least 1 range of open hours) on a day in the regular schedule.

```php
$openingHours->isOpenOn('saturday');
```

If the given string is a date, it will check if it's open (contains at least 1 range of open hours) considering
both regular day schedule and possible exceptions.

```php
$openingHours->isOpenOn('2020-09-03');
$openingHours->isOpenOn('09-03'); // If year is omitted, current year is used instead
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

#### `OpeningHours::nextOpen(DateTimeInterface $dateTime) : DateTime`

Returns next open DateTime from the given DateTime

```php
$openingHours->nextOpen(new DateTime('2016-12-24 11:00:00'));
```

#### `OpeningHours::nextClose(DateTimeInterface $dateTime) : DateTime`

Returns next close DateTime from the given DateTime

```php
$openingHours->nextClose(new DateTime('2016-12-24 11:00:00'));
```

#### `OpeningHours::previousOpen(DateTimeInterface $dateTime) : DateTime`

Returns previous open DateTime from the given DateTime

```php
$openingHours->previousOpen(new DateTime('2016-12-24 11:00:00'));
```

#### `OpeningHours::previousClose(DateTimeInterface $dateTime) : DateTime`

Returns previous close DateTime from the given DateTime

```php
$openingHours->nextClose(new DateTime('2016-12-24 11:00:00'));
```

#### `OpeningHours::diffInOpenHours(DateTimeInterface $startDate, DateTimeInterface $endDate) : float`

Return the amount of open time (number of hours as a floating number) between 2 dates/times.

```php
$openingHours->diffInOpenHours(new DateTime('2016-12-24 11:00:00'), new DateTime('2016-12-24 16:34:25'));
```

#### `OpeningHours::diffInOpenMinutes(DateTimeInterface $startDate, DateTimeInterface $endDate) : float`

Return the amount of open time (number of minutes as a floating number) between 2 dates/times.

#### `OpeningHours::diffInOpenSeconds(DateTimeInterface $startDate, DateTimeInterface $endDate) : float`

Return the amount of open time (number of seconds as a floating number) between 2 dates/times.

#### `OpeningHours::diffInClosedHours(DateTimeInterface $startDate, DateTimeInterface $endDate) : float`

Return the amount of closed time (number of hours as a floating number) between 2 dates/times.

```php
$openingHours->diffInClosedHours(new DateTime('2016-12-24 11:00:00'), new DateTime('2016-12-24 16:34:25'));
```

#### `OpeningHours::diffInClosedMinutes(DateTimeInterface $startDate, DateTimeInterface $endDate) : float`

Return the amount of closed time (number of minutes as a floating number) between 2 dates/times.

#### `OpeningHours::diffInClosedSeconds(DateTimeInterface $startDate, DateTimeInterface $endDate) : float`

Return the amount of closed time (number of seconds as a floating number) between 2 dates/times.

#### `OpeningHours::currentOpenRange(DateTimeInterface $dateTime) : false | TimeRange`

Returns a `Spatie\OpeningHours\TimeRange` instance of the current open range if the
business is open, false if the business is closed.

```php
$range = $openingHours->currentOpenRange(new DateTime('2016-12-24 11:00:00'));

if ($range) {
    echo "It's open since ".$range->start()."\n";
    echo "It will close at ".$range->end()."\n";
} else {
    echo "It's closed";
}
```

#### `OpeningHours::currentOpenRangeStart(DateTimeInterface $dateTime) : false | DateTime`

Returns a `DateTime` instance of the date and time since when the business is open if
the business is open, false if the business is closed.

Note: date can be the previous day if you use night ranges.

```php
$date = $openingHours->currentOpenRangeStart(new DateTime('2016-12-24 11:00:00'));

if ($date) {
    echo "It's open since ".$date->format('H:i');
} else {
    echo "It's closed";
}
```

#### `OpeningHours::currentOpenRangeEnd(DateTimeInterface $dateTime) : false | DateTime`

Returns a `DateTime` instance of the date and time until when the business will be open
if the business is open, false if the business is closed.

Note: date can be the next day if you use night ranges.

```php
$date = $openingHours->currentOpenRangeEnd(new DateTime('2016-12-24 11:00:00'));

if ($date) {
    echo "It will close at ".$date->format('H:i');
} else {
    echo "It's closed";
}
```

#### `OpeningHours::asStructuredData(strinf $format = 'H:i', string|DateTimeZone $timezone) : array`

Returns a [OpeningHoursSpecification](https://schema.org/openingHoursSpecification) as an array.

```php
$openingHours->asStructuredData();
$openingHours->asStructuredData('H:i:s'); // Customize time format, could be 'h:i a', 'G:i', etc.
$openingHours->asStructuredData('H:iP', '-05:00'); // Add a timezone
// Timezone can be numeric or string like "America/Toronto" or a DateTimeZone instance
// But be careful, the time is arbitrary applied on 1970-01-01, so it does not handle daylight
// saving time, meaning Europe/Paris is always +01:00 even in summer time.
```

### `Spatie\OpeningHours\OpeningHoursForDay`

This class is meant as read-only. It implements `ArrayAccess`, `Countable` and `IteratorAggregate` so you can process the list of `TimeRange`s in an array-like way.

### `Spatie\OpeningHours\TimeRange`

Value object describing a period with a start and an end time. Can be cast to a string in a `H:i-H:i` format.

### `Spatie\OpeningHours\Time`

Value object describing a single time. Can be cast to a string in a `H:i` format.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Kruikstraat 22, 2018 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Sebastian De Deyne](https://github.com/sebastiandedeyne)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
