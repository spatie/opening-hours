<?php

namespace Spatie\OpeningHours;

use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;

class TimeRange
{
    /** @var \Spatie\OpeningHours\Time */
    protected $start;

    /** @var \Spatie\OpeningHours\Time */
    protected $end;

    protected function __construct(Time $start, Time $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public static function fromString(string $string): self
    {
        $times = explode('-', $string);

        if (count($times) !== 2) {
            throw InvalidTimeRangeString::forString($string);
        }

        return new self(Time::fromString($times[0]), Time::fromString($times[1]));
    }

    public function start(): Time
    {
        return $this->start;
    }

    public function end(): Time
    {
        return $this->end;
    }

    public function spillsOverToNextDay(): bool
    {
        return $this->end->isBefore($this->start);
    }

    public function containsTime(Time $time): bool
    {
        if ($this->spillsOverToNextDay()) {
            if ($time->isAfter($this->start)) {
                return $time->isAfter($this->end);
            }

            return $time->isBefore($this->end);
        }

        return $time->isSameOrAfter($this->start) && $time->isBefore($this->end);
    }

    public function overlaps(TimeRange $timeRange): bool
    {
        return $this->containsTime($timeRange->start) || $this->containsTime($timeRange->end);
    }

    public function format(string $timeFormat = 'H:i', string $rangeFormat = '%s-%s'): string
    {
        return sprintf($rangeFormat, $this->start->format($timeFormat), $this->end->format($timeFormat));
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
