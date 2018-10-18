# Changelog

All notable changes to `opening-hours` will be documented in this file

## 1.8.1 - 2018-10-18

- overspilling timerange will now contain start time

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

