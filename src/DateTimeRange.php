<?php

namespace Spatie\OpeningHours;

use DateTimeInterface;

class DateTimeRange extends TimeRange
{
    protected DateTimeInterface $date;

    protected function __construct(DateTimeInterface $date, Time $start, Time $end, $data = null)
    {
        $this->date = $date;
        parent::__construct(
            Time::fromString($start, $start->getData(), $this->startDate()),
            Time::fromString($end, $start->getData(), $this->endDate()),
            $data
        );
    }

    public static function fromTimeRange(DateTimeInterface $date, TimeRange $timeRange, $data = null)
    {
        return new self($date, $timeRange->start, $timeRange->end, $data);
    }

    public function startDate(): DateTimeInterface
    {
        return $this->copyAndModify($this->date, $this->start.(
            $this->start > $this->date->format(self::TIME_FORMAT)
                ? ' - 1 day'
                : ''
            ));
    }

    public function endDate(): DateTimeInterface
    {
        return $this->copyAndModify($this->date, $this->end.(
            $this->end < $this->date->format(self::TIME_FORMAT)
                ? ' + 1 day'
                : ''
            ));
    }
}
