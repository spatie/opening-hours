<?php

namespace Spatie\OpeningHours;

class TimeRange
{
    /** @var \Spatie\OpeningHours\Time */
    protected $start, $end;

    protected function __construct(Time $start, Time $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public static function fromString(string $string)
    {
        $string = str_replace(' ', '', $string);
        $times = explode('-', $string);

        if (count($times) !== 2) {
            throw new \InvalidArgumentException();
        }

        return new static(Time::fromString($times[0]), Time::fromString($times[1]));
    }

    public function containsTime(Time $time): bool
    {
        if ($this->spillsOverToNextDay()) {
            return $time->isSameOrAfter($this->start) && $time->isAfter($this->end);
        }

        return $time->isSameOrAfter($this->start) && $time->isBefore($this->end);
    }

    public function spillsOverToNextDay(): bool
    {
        return $this->end->isBefore($this->start);
    }

    public function __toString()
    {
        return "{$this->start}-{$this->end}";
    }
}
