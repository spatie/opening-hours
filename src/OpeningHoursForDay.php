<?php

namespace Spatie\OpeningHours;

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Helpers\DataTrait;
use Spatie\OpeningHours\Exceptions\NonMutableOffsets;
use Spatie\OpeningHours\Exceptions\OverlappingTimeRanges;

class OpeningHoursForDay implements ArrayAccess, Countable, IteratorAggregate
{
    use DataTrait;

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

    /**
     * @param callable[] $filters
     *
     * @return Time|bool
     */
    public function openingHoursFilter(array $filters)
    {
        foreach ($this->openingHours as $timeRange) {
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
                return $this->findNextOpenInFreeTime($time, $timeRange);
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
                return $this->findNextCloseInWorkingHours($time, $timeRange);
            },
            function ($timeRange) use ($time) {
                return $this->findNextCloseInFreeTime($time, $timeRange);
            },
        ]);
    }

    protected function findNextOpenInFreeTime(Time $time, TimeRange $timeRange)
    {
        if (TimeRange::fromString('00:00-'.$timeRange->start())->containsTime($time)) {
            return $timeRange->start();
        }
    }

    protected function findNextCloseInWorkingHours(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->containsTime($time)) {
            return next($timeRange);
        }
    }

    protected function findNextCloseInFreeTime(Time $time, TimeRange $timeRange)
    {
        if (TimeRange::fromString('00:00-'.$timeRange->start())->containsTime($time)) {
            return $timeRange->end();
        }
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
