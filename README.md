# A helper to query and format a set of opening hours

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/opening-hours.svg?style=flat-square)](https://packagist.org/packages/spatie/opening-hours)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/opening-hours/master.svg?style=flat-square)](https://travis-ci.org/spatie/opening-hours)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/xxxxxxxxx.svg?style=flat-square)](https://insight.sensiolabs.com/projects/xxxxxxxxx)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/opening-hours.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/opening-hours)
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
$openingHours->forDay('monday'); // TimeRange[] for the regular schedule
$openingHours->forWeek(); // TimeRange[][] for the regular schedule, keyed by day name
$openingHours->forDate(new DateTime('2016-12-25')); // TimeRange[] for a specific day
$openingHours->exceptions(); // TimeRange[][] of all exceptions, keyed by date
```

Read the usage section for the full api.

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment you are required to send us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

The best postcards will get published on the open source page on our website.

## Installation

**Note:** Remove this paragraph if you are building a public package  
This package is custom built for [Spatie](https://spatie.be) projects and is therefore not registered on packagist. In order to install it via composer you must specify this extra repository in `composer.json`:

```json
"repositories": [ { "type": "composer", "url": "https://satis.spatie.be/" } ]
```

You can install the package via composer:

``` bash
composer require spatie/opening-hours
```

## Usage

``` php
$skeleton = new Spatie\Skeleton();
echo $skeleton->echoPhrase('Hello, Spatie!');
```

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
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
