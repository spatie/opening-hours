<?php

namespace Spatie\OpeningHours\Helpers;

use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

trait RangeFinder
{
    protected function findRangeInFreeTime(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->start()->isAfter($time)) {
            return $timeRange;
        }
    }

    protected function findOpenInFreeTime(Time $time, TimeRange $timeRange)
    {
        $range = $this->findRangeInFreeTime($time, $timeRange);

        if ($range) {
            return $range->start();
        }
    }

    protected function findOpenRangeInWorkingHours(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->start()->isBefore($time)) {
            return $timeRange;
        }
    }

    protected function findOpenInWorkingHours(Time $time, TimeRange $timeRange)
    {
        $range = $this->findOpenRangeInWorkingHours($time, $timeRange);

        if ($range) {
            return $range->start();
        }
    }

    protected function findCloseInWorkingHours(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->containsTime($time)) {
            return $timeRange->end();
        }
    }

    protected function findCloseRangeInWorkingHours(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->containsTime($time)) {
            return $timeRange;
        }
    }

    protected function findCloseInFreeTime(Time $time, TimeRange $timeRange)
    {
        $range = $this->findRangeInFreeTime($time, $timeRange);

        if ($range) {
            return $range->end();
        }
    }

    protected function findPreviousRangeInFreeTime(Time $time, TimeRange $timeRange)
    {
        if ($timeRange->end()->isBefore($time)) {
            return $timeRange;
        }
    }

    protected function findPreviousOpenInFreeTime(Time $time, TimeRange $timeRange)
    {
        $range = $this->findPreviousRangeInFreeTime($time, $timeRange);

        if ($range) {
            return $range->start();
        }
    }

    protected function findPreviousCloseInWorkingHours(Time $time, TimeRange $timeRange)
    {
        $end = $timeRange->end();

        if ($end->isBefore($time)) {
            return $end;
        }
    }
}
