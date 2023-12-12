<?php

namespace Spatie\OpeningHours;

use DateTimeInterface;

readonly class DateTimeRange extends TimeRange
{
    protected function __construct(
        protected DateTimeInterface $date,
        Time $start,
        Time $end,
        mixed $data = null,
    ) {
        $startDate = $this->copyAndModify($date, $start.(
            $start > $date->format(self::TIME_FORMAT)
                ? ' - 1 day'
                : ''
        ));
        $endDate = $this->copyAndModify($date, $end.(
            $end < $date->format(self::TIME_FORMAT)
                ? ' + 1 day'
                : ''
        ));
        parent::__construct(
            Time::fromString($start, $start->data, $startDate),
            Time::fromString($end, $start->data, $endDate),
            $data,
        );
    }

    public static function fromTimeRange(DateTimeInterface $date, TimeRange $timeRange, mixed $data = null)
    {
        return new self($date, $timeRange->start, $timeRange->end, $data);
    }
}
