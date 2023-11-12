<?php

namespace Spatie\OpeningHours;

use DateTimeInterface;

readonly class DateTimeRange extends TimeRange
{
    protected DateTimeInterface $date;

    protected function __construct(DateTimeInterface $date, Time $start, Time $end, mixed $data = null)
    {
        $this->date = $date;
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
            Time::fromString($start, $start->getData(), $startDate),
            Time::fromString($end, $start->getData(), $endDate),
            $data
        );
    }

    public static function fromTimeRange(DateTimeInterface $date, TimeRange $timeRange, mixed $data = null)
    {
        return new self($date, $timeRange->start, $timeRange->end, $data);
    }
}
