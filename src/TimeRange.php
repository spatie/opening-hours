<?php

namespace Spatie\OpeningHours;

use Spatie\OpeningHours\Helpers\DataTrait;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeList;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeArray;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;

class TimeRange
{
    use DataTrait;

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

    public static function fromArray(array $array): self
    {
        $values = [];
        $keys = ['hours', 'data'];

        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $values[$key] = $array[$key];
                unset($array[$key]);

                continue;
            }
        }

        foreach ($keys as $key) {
            if (! isset($values[$key])) {
                $values[$key] = array_shift($array);
            }
        }

        if (! $values['hours']) {
            throw InvalidTimeRangeArray::create();
        }

        return static::fromString($values['hours'])->setData($values['data']);
    }

    public static function fromDefinition($value): self
    {
        return is_array($value) ? static::fromArray($value) : static::fromString($value);
    }

    public static function fromList(array $ranges): self
    {
        if (count($ranges) === 0) {
            throw InvalidTimeRangeList::create();
        }

        foreach ($ranges as $range) {
            if (! ($range instanceof self)) {
                throw InvalidTimeRangeList::create();
            }
        }

        $start = $ranges[0]->start();
        $end = $ranges[0]->end();

        foreach (array_slice($ranges, 1) as $range) {
            $rangeStart = $range->start();
            if ($rangeStart->format('Gi') < $start->format('Gi')) {
                $start = $rangeStart;
            }
            $rangeEnd = $range->end();
            if ($rangeEnd->format('Gi') > $end->format('Gi')) {
                $end = $rangeEnd;
            }
        }

        return new self($start, $end);
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
            if ($time->isSameOrAfter($this->start)) {
                return $time->isAfter($this->end);
            }

            return $time->isBefore($this->end);
        }

        return $time->isSameOrAfter($this->start) && $time->isBefore($this->end);
    }

    public function overlaps(self $timeRange): bool
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
