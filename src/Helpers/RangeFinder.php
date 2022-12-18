<?php

namespace Spatie\OpeningHours\Helpers;

use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

trait RangeFinder
{
    protected function findRangeInFreeTime(Time $time, TimeRange $timeRange): ?TimeRange
    {
        return $time->isBefore($timeRange->start()) ? $timeRange : null;
    }

    protected function findOpenInFreeTime(Time $time, TimeRange $timeRange): ?Time
    {
        $range = $this->findRangeInFreeTime($time, $timeRange);

        return $range ? $range->start() : null;
    }

    protected function findOpenRangeInWorkingHours(Time $time, TimeRange $timeRange): ?TimeRange
    {
        return $time->isAfter($timeRange->start()) ? $timeRange : null;
    }

    protected function findOpenInWorkingHours(Time $time, TimeRange $timeRange): ?Time
    {
        $range = $this->findOpenRangeInWorkingHours($time, $timeRange);

        return $range ? $range->start() : null;
    }

    protected function findCloseInWorkingHours(Time $time, TimeRange $timeRange): ?Time
    {
        return $timeRange->containsTime($time) ? $timeRange->end() : null;
    }

    protected function findCloseRangeInWorkingHours(Time $time, TimeRange $timeRange): ?TimeRange
    {
        return $timeRange->containsTime($time) ? $timeRange : null;
    }

    protected function findCloseInFreeTime(Time $time, TimeRange $timeRange): ?Time
    {
        $range = $this->findRangeInFreeTime($time, $timeRange);

        return $range ? $range->end() : null;
    }

    protected function findPreviousRangeInFreeTime(Time $time, TimeRange $timeRange): ?TimeRange
    {
        return $time->isAfter($timeRange->end()) ? $timeRange : null;
    }

    protected function findPreviousOpenInFreeTime(Time $time, TimeRange $timeRange): ?Time
    {
        $range = $this->findPreviousRangeInFreeTime($time, $timeRange);

        return $range ? $range->start() : null;
    }

    protected function findPreviousCloseInWorkingHours(Time $time, TimeRange $timeRange): ?Time
    {
        $end = $timeRange->end();

        return $time->isAfter($end) ? $end : null;
    }
}
