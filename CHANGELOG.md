# Changelog

All Notable changes to `opening-hours` will be documented in this file

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

