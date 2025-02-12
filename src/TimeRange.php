<?php

namespace Spatie\OpeningHours;

use Spatie\OpeningHours\Exceptions\InvalidTimeRangeArray;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeList;
use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;
use Spatie\OpeningHours\Helpers\DataTrait;
use Spatie\OpeningHours\Helpers\DateTimeCopier;

readonly class TimeRange implements TimeDataContainer
{
    use DataTrait;
    use DateTimeCopier;

    protected function __construct(
        protected Time $start,
        protected Time $end,
        public mixed $data = null,
    ) {
    }

    public static function fromString(string $string, $data = null): self
    {
        $times = explode('-', $string);

        if (count($times) !== 2) {
            throw InvalidTimeRangeString::forString($string);
        }

        return new self(Time::fromString($times[0]), Time::fromString($times[1]), $data);
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

        return static::fromString($values['hours'], $values['data']);
    }

    public static function fromDefinition($value): self
    {
        return is_array($value) ? static::fromArray($value) : static::fromString($value);
    }

    public static function fromList(array $ranges, $data = null): self
    {
        if (count($ranges) === 0) {
            throw InvalidTimeRangeList::create();
        }

        foreach ($ranges as $range) {
            if (! ($range instanceof self)) {
                throw InvalidTimeRangeList::create();
            }
        }

        $start = $ranges[0]->start;
        $end = $ranges[0]->end;

        foreach (array_slice($ranges, 1) as $range) {
            if ($range->start->isBefore($start)) {
                $start = $range->start;
            }

            if ($range->end->isAfter($end)) {
                $end = $range->end;
            }
        }

        return new self($start, $end, $data);
    }

    public static function fromMidnight(Time $end, $data = null): self
    {
        return new self(Time::fromString(self::MIDNIGHT), $end, $data);
    }

    public function start(): Time
    {
        return $this->start;
    }

    public function end(): Time
    {
        return $this->end;
    }

    public function isReversed(): bool
    {
        return $this->start->isAfter($this->end);
    }

    public function overflowsNextDay(): bool
    {
        return $this->isReversed();
    }

    public function spillsOverToNextDay(): bool
    {
        return $this->isReversed();
    }

    public function containsTime(Time $time): bool
    {
        return $time->isSameOrAfter($this->start) && ($this->overflowsNextDay() || $time->isBefore($this->end));
    }

    public function containsNightTime(Time $time): bool
    {
        return $this->overflowsNextDay() && self::fromMidnight($this->end)->containsTime($time);
    }

    public function overlaps(self $timeRange): bool
    {
        return $this->containsTime($timeRange->start) || $this->containsTime($timeRange->end);
    }

    public function format(string $timeFormat = self::TIME_FORMAT, string $rangeFormat = '%s-%s', $timezone = null): string
    {
        return sprintf($rangeFormat, $this->start->format($timeFormat, $timezone), $this->end->format($timeFormat, $timezone));
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
