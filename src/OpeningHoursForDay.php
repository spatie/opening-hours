<?php

namespace Spatie\OpeningHours;

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Exceptions\OverlappingTimeRanges;

class OpeningHoursForDay implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var array */
    protected $openingHours = [];

    public static function fromStrings(array $strings)
    {
        $openingHoursForDay = new static();

        $timeRanges = Arr::map($strings, function ($string) {
            return TimeRange::fromString($string);
        });

        $openingHoursForDay->guardAgainstTimeRangeOverlaps($timeRanges);

        $openingHoursForDay->openingHours = $timeRanges;

        return $openingHoursForDay;
    }

    public function isOpenAt(Time $time)
    {
        foreach ($this->openingHours as $timeRange) {
            if ($timeRange->containsTime($time)) {
                return true;
            }
        }

        return false;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->openingHours[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->openingHours[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception();
    }

    public function offsetUnset($offset)
    {
        unset($this->openingHours[$offset]);
    }

    public function count(): int
    {
        return count($this->openingHours);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->openingHours);
    }

    protected function guardAgainstTimeRangeOverlaps(array $openingHours)
    {
        foreach (Arr::createUniquePairs($openingHours) as $timeRanges) {
            if ($timeRanges[0]->overlaps($timeRanges[1])) {
                throw OverlappingTimeRanges::forRanges($timeRanges[0], $timeRanges[1]);
            }
        }
    }
}
