# Changelog

All notable changes to `opening-hours` will be documented in this file

## 2.7.1 - 2020-05-30

- Added `InvalidTimezone` exception

## 2.7.0 - 2019-08-27

- Added `forWeekConsecutiveDays()` method

## 2.6.0 - 2019-07-18

- Allowed to retrieve current and previous opening hours
- Added `previousOpen()`
- Added `previousClose()`
- Added `currentOpenRange()`
- Added `currentOpenRangeStart()`
- Added `currentOpenRangeEnd()`

## 2.5.0 - 2019-06-19

- Allowed [#128](https://github.com/spatie/opening-hours/issues/128) un-ordered ranges

## 2.4.1 - 2019-06-19

- Added [#121](https://github.com/spatie/opening-hours/issues/121) timezone supporrt in `TimeRange::format()`

## 2.4.0 - 2019-06-19

- Added [#121](https://github.com/spatie/opening-hours/issues/121) custom format and timezone support in `asStructuredData()`

## 2.3.3 - 2019-06-15

- Fixed merge when last range of day ends with `24:00`

## 2.3.2 - 2019-06-10

- Fixed [#115](https://github.com/spatie/opening-hours/issues/115) return `24:00` when `Time::fromString('24:00')` is casted to string

## 2.3.1 - 2019-06-07

- Added a `MaximumLimitExceeded` exception to prevent infinite loop

## 2.3.0 - 2019-06-05

⚠ TimeRange no longer return true on containsTime for times overflowing next day.
Overflow is now calculated at the day level (OpeningHoursForDay).

- Added `OpeningHoursForDay::isOpenAtNight()`
- Added `TimeRange::overflowsNextDay()`

## 2.2.1 - 2019-06-04

- Fixed [#111](https://github.com/spatie/opening-hours/issues/111) overflow with simple ranges and add tests

## 2.2.0 - 2019-05-07

- Allowed opening hours overflowing on the next day by passing `'overflow' => true` option in array definition

## 2.1.2 - 2019-03-14

- Fixed [#98](https://github.com/spatie/opening-hours/issues/98) Set precise time bounds

## 2.1.1 - 2019-02-22

- Fixed [#95](https://github.com/spatie/opening-hours/issues/95) Handle hours/data in any order

## 2.1.0 - 2019-02-18

- Fixed [#88](https://github.com/spatie/opening-hours/issues/88) Opening hours across Midnight
- Fixed [#89](https://github.com/spatie/opening-hours/issues/89) Data support for next open hours
- Implemented [#93](https://github.com/spatie/opening-hours/issues/93) Enable PHP 8

## 2.0.0 - 2018-12-13

- Added support for immutable dates
- Allowed to add meta-data to global/exceptions config, days config, ranges settings via `setData()` and `getData()`
- Allowed dynamic opening hours settings
- Added `TimeRange::fromArray()` and `TimeRange::fromDefinition()` (to support array of hours+data or string[] or string)
- Added `setFilters()` and `getFilters()`

⚠ Breaking changes:
- `nextOpen()` and `nextClose()` return type changed for `DateTimeInterface` as it can now return `DateTimeImmutable` too
- `toDateTime()` changed both input type and return type for `DateTimeInterface` as it can now take and return `DateTimeImmutable` too

## 1.9.0 - 2018-12-07

- Allowed to merge overlapping hours [#43](https://github.com/spatie/opening-hours/issues/43)
- Fixed `nextOpen()` and `nextClose()` consecutive calls [#73](https://github.com/spatie/opening-hours/issues/73)

## 1.8.1 - 2018-10-18

- Added start time to overspilling timeranges

## 1.8.0 - 2018-09-17
- Added `nextClose`

## 1.7.0 - 2018-08-02
- Added additional helpers on `Time`

## 1.6.0 - 2018-03-26
- Added the ability to pass a `DateTime` instance to mutate to `Time::toDateTime`

## 1.5.0 - 2018-02-26
- Added `OpeningHours::forWeekCombined()`

## 1.4.0 - 2017-09-15
- Added the ability to add recurring exceptions

## 1.3.1 - 2017-09-13
- Fixed bug where checking on times starting at midnight would cause an infinite loop

## 1.3.0 - 2017-06-01
- Added `regularClosingDays`, `regularClosingDaysISO` and `exceptionalClosingDates` methods

## 1.2.0 - 2017-01-03
- Added `asStructuredData` to retrieve the opening hours as a Schema.org structured data array
- Added `nextOpen` method to determine the next time the business will be open
- Added utility methods: `OpeningHours::map`, `OpeningHours::flatMap`, `OpeningHours::mapExceptions`, `OpeningHours::flatMapExceptions`,`OpeningHoursForDay::map` and `OpeningHoursForDay::empty`

## 1.1.0 - 2016-11-09
- Added timezone support

## 1.0.3 - 2016-10-18
- `isClosedOn` fix

## 1.0.2 - 2016-10-13

- Fixed missing import in `Time` class

## 1.0.1 - 2016-10-13

- Replaced `DateTime` by `DateTimeInterface`

## 1.0.0 - 2016-10-07

- First release

