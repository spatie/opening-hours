<?php

namespace Spatie\OpeningHours;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use Spatie\OpeningHours\Exceptions\NonMutableOffsets;
use Spatie\OpeningHours\Exceptions\OverlappingTimeRanges;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Helpers\DataTrait;
use Spatie\OpeningHours\Helpers\RangeFinder;

class OpeningHoursForDay implements ArrayAccess, Countable, IteratorAggregate
{
    use DataTrait, RangeFinder;

    /** @var \Spatie\OpeningHours\TimeRange[] */
    protected $openingHours = [];

    public static function fromStrings(array $strings)
    {
        if (isset($strings['hours'])) {
            return static::fromStrings($strings['hours'])->setData($strings['data'] ?? null);
        }

        $openingHoursForDay = new static();

        if (isset($strings['data'])) {
            $openingHoursForDay->setData($strings['data'] ?? null);
            unset($strings['data']);
        }

        uasort($strings, function ($a, $b) {
            return strcmp(static::getHoursFromRange($a), static::getHoursFromRange($b));
        });

        $timeRanges = Arr::map($strings, function ($string) {
            return TimeRange::fromDefinition($string);
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

    public function isOpenAtNight(Time $time)
    {
        foreach ($this->openingHours as $timeRange) {
            if ($timeRange->containsNightTime($time)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable[] $filters
     * @param bool       $reverse
     *
     * @return Time|bool
     */
    public function openingHoursFilter(array $filters, bool $reverse = false)
    {
        foreach (($reverse ? array_reverse($this->openingHours) : $this->openingHours) as $timeRange) {
            foreach ($filters as $filter) {
                if ($result = $filter($timeRange)) {
                    reset($timeRange);

                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * @param Time $time
     *
     * @return bool|Time
     */
    public function nextOpen(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findOpenInFreeTime($time, $timeRange);
            },
        ]);
    }

    /**
     * @param Time $time
     *
     * @return bool|TimeRange
     */
    public function nextOpenRange(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findRangeInFreeTime($time, $timeRange);
            },
        ]);
    }

    /**
     * @param Time $time
     *
     * @return bool|Time
     */
    public function nextClose(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findCloseInWorkingHours($time, $timeRange);
            },
            function ($timeRange) use ($time) {
                return $this->findCloseInFreeTime($time, $timeRange);
            },
        ]);
    }

    /**
     * @param Time $time
     *
     * @return bool|TimeRange
     */
    public function nextCloseRange(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findCloseRangeInWorkingHours($time, $timeRange);
            },
            function ($timeRange) use ($time) {
                return $this->findRangeInFreeTime($time, $timeRange);
            },
        ]);
    }

    /**
     * @param Time $time
     *
     * @return bool|Time
     */
    public function previousOpen(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findPreviousOpenInFreeTime($time, $timeRange);
            },
            function ($timeRange) use ($time) {
                return $this->findOpenInWorkingHours($time, $timeRange);
            },
        ], true);
    }

    /**
     * @param Time $time
     *
     * @return bool|TimeRange
     */
    public function previousOpenRange(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findRangeInFreeTime($time, $timeRange);
            },
        ], true);
    }

    /**
     * @param Time $time
     *
     * @return bool|Time
     */
    public function previousClose(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findPreviousCloseInWorkingHours($time, $timeRange);
            },
        ], true);
    }

    /**
     * @param Time $time
     *
     * @return bool|TimeRange
     */
    public function previousCloseRange(Time $time)
    {
        return $this->openingHoursFilter([
            function ($timeRange) use ($time) {
                return $this->findPreviousRangeInFreeTime($time, $timeRange);
            },
        ], true);
    }

    protected static function getHoursFromRange($range)
    {
        return strval((is_array($range)
            ? ($range['hours'] ?? array_values($range)[0] ?? null)
            : null
        ) ?: $range);
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
        throw NonMutableOffsets::forClass(static::class);
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

    /**
     * @param Time $time
     *
     * @return TimeRange[]
     */
    public function forTime(Time $time): Generator
    {
        foreach ($this as $range) {
            /* @var TimeRange $range */

            if ($range->containsTime($time)) {
                yield $range;
            }
        }
    }

    /**
     * @param Time $time
     *
     * @return TimeRange[]
     */
    public function forNightTime(Time $time): Generator
    {
        foreach ($this as $range) {
            /* @var TimeRange $range */

            if ($range->containsNightTime($time)) {
                yield $range;
            }
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->openingHours);
    }

    public function map(callable $callback): array
    {
        return Arr::map($this->openingHours, $callback);
    }

    protected function guardAgainstTimeRangeOverlaps(array $openingHours)
    {
        foreach (Arr::createUniquePairs($openingHours) as $timeRanges) {
            if ($timeRanges[0]->overlaps($timeRanges[1])) {
                throw OverlappingTimeRanges::forRanges($timeRanges[0], $timeRanges[1]);
            }
        }
    }

    public function __toString()
    {
        $values = [];

        foreach ($this->openingHours as $openingHour) {
            $values[] = (string) $openingHour;
        }

        return implode(',', $values);
    }
}
