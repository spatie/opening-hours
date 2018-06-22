<?php

namespace Spatie\OpeningHours;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Spatie\OpeningHours\Exceptions\OverlappingTimeRanges;
use Spatie\OpeningHours\Helpers\Arr;

class OpeningHoursForDay implements ArrayAccess, Countable, IteratorAggregate {
    /** @var \Spatie\OpeningHours\TimeRange[] */
    protected $openingHours = [];

    /**
     * @param array $strings
     * @return OpeningHoursForDay
     */
    public static function fromStrings(array $strings) {
        $openingHoursForDay = new static();

        $timeRanges = Arr::map($strings, function ($string) {
            return TimeRange::fromString($string);
        });

        $openingHoursForDay->guardAgainstTimeRangeOverlaps($timeRanges);

        $openingHoursForDay->openingHours = $timeRanges;

        return $openingHoursForDay;
    }

    /**
     * @param Time $time
     * @return bool
     */
    public function isOpenAt(Time $time) {
        foreach ($this->openingHours as $timeRange) {
            if ($timeRange->containsTime($time)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Time $time
     * @return bool|mixed|Time
     * @throws Exceptions\InvalidTimeRangeString
     * @throws Exceptions\InvalidTimeString
     */
    public function nextOpen(Time $time) {
        foreach ($this->openingHours as $timeRange) {
            if ($nextOpen = $this->findNextOpenInWorkingHours($time, $timeRange)) {
                return $nextOpen;
            }

            if ($nextOpen = $this->findNextOpenInFreeTime($time, $timeRange)) {
                return $nextOpen;
            }
        }

        return false;
    }

    /**
     * @param Time $time
     * @param TimeRange $timeRange
     * @return TimeRange|null
     */
    protected function findNextOpenInWorkingHours(Time $time, TimeRange $timeRange) {
        if ($timeRange->containsTime($time) && next($timeRange) !== $timeRange) {
            return next($timeRange);
        }

        return null;
    }

    /**
     * @param Time $time
     * @param TimeRange $timeRange
     * @param TimeRange|null $prevTimeRange
     * @return Time|null
     * @throws Exceptions\InvalidTimeRangeString
     * @throws Exceptions\InvalidTimeString
     */
    protected function findNextOpenInFreeTime(Time $time, TimeRange $timeRange, TimeRange &$prevTimeRange = null) {
        $timeOffRange = $prevTimeRange
            ?
            TimeRange::fromString($prevTimeRange->end() . '-' . $timeRange->start())
            :
            TimeRange::fromString('00:00-' . $timeRange->start());

        if ($timeOffRange->containsTime($time) || $timeOffRange->start()->isSame($time)) {
            return $timeRange->start();
        }

        $prevTimeRange = $timeRange;

        return null;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->openingHours[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|TimeRange
     */
    public function offsetGet($offset) {
        return $this->openingHours[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \Exception
     */
    public function offsetSet($offset, $value) {
        throw new \Exception();
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->openingHours[$offset]);
    }

    /**
     * @return int
     */
    public function count() {
        return count($this->openingHours);
    }

    /**
     * @return ArrayIterator|\Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->openingHours);
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        return empty($this->openingHours);
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function map(callable $callback) {
        return Arr::map($this->openingHours, $callback);
    }

    /**
     * @param array $openingHours
     * @throws OverlappingTimeRanges
     */
    protected function guardAgainstTimeRangeOverlaps(array $openingHours) {
        foreach (Arr::createUniquePairs($openingHours) as $timeRanges) {
            /* @var $timeRanges TimeRange[] */
            if ($timeRanges[0]->overlaps($timeRanges[1])) {
                throw OverlappingTimeRanges::forRanges($timeRanges[0], $timeRanges[1]);
            }
        }
    }

    /**
     * @return string
     */
    public function __toString() {
        $values = [];
        foreach ($this->openingHours as $openingHour) {
            $values[] = (string)$openingHour;
        }

        return implode(',', $values);
    }
}
