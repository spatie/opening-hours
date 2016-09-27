<?php

namespace Spatie\OpeningHours;

use ArrayAccess;
use Countable;

class OpeningHoursForDay implements ArrayAccess, Countable
{
    /** @var array */
    protected $openingHours = [];

    public static function fromStrings(array $strings)
    {
        $openingHoursForDay = new static();

        $timeRanges = array_map(function ($string) {
            return TimeRange::fromString($string);
        }, $strings);

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

    public function offsetExists($offset)
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

    public function count()
    {
        return count($this->openingHours);
    }

    protected function guardAgainstTimeRangeOverlaps(array $openingHours)
    {
    }
}
