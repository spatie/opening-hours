<?php

namespace Spatie\OpeningHours;

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Helpers\DataTrait;
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

    public function nextOpen(Time $time)
    {
        foreach ($this->openingHours as $timeRange) {
            if ($nextOpen = $this->findNextOpenInWorkingHours($time, $timeRange)) {
                reset($timeRange);

                return $nextOpen;
            }

            if ($nextOpen = $this->findNextOpenInFreeTime($time, $timeRange)) {
                reset($timeRange);

                return $nextOpen;
            }
        }

        return false;
    }

    public function nextClose(Time $time)
    {
        foreach ($this->openingHours as $timeRange) {
            if ($nextClose = $this->findNextCloseInWorkingHours($time, $timeRange)) {
                reset($timeRange);

                return $nextClose;
            }

            if ($nextClose = $this->findNextCloseInFreeTime($time, $timeRange)) {
                reset($timeRange);

                return $nextClose;
            }
        }

        return false;
    }

    protected function findNextOpenInWorkingHours(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->containsTime($time) && next($timeRange) !== $timeRange) {
            return next($timeRange);
        }
    }

    protected function findNextOpenInFreeTime(Time $time, TimeRange $timeRange, TimeRange &$prevTimeRange = null)
    {
        $timeOffRange = $prevTimeRange ?
            TimeRange::fromString($prevTimeRange->end().'-'.$timeRange->start()) :
            TimeRange::fromString('00:00-'.$timeRange->start());

        if ($timeOffRange->containsTime($time) || $timeOffRange->start()->isSame($time)) {
            return $timeRange->start();
        }

        $prevTimeRange = $timeRange;
    }

    protected function findNextCloseInWorkingHours(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->containsTime($time)) {
            return next($timeRange);
        }
    }

    protected function findNextCloseInFreeTime(Time $time, TimeRange $timeRange, TimeRange &$prevTimeRange = null)
    {
        $timeOffRange = $prevTimeRange ?
            TimeRange::fromString($prevTimeRange->end().'-'.$timeRange->start()) :
            TimeRange::fromString('00:00-'.$timeRange->start());

        if ($timeOffRange->containsTime($time) || $timeOffRange->start()->isSame($time)) {
            return $timeRange->end();
        }

        $prevTimeRange = $timeRange;
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
