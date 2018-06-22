<?php

namespace Spatie\OpeningHours;

use Spatie\OpeningHours\Exceptions\InvalidTimeRangeString;

class TimeRange {
    /** @var \Spatie\OpeningHours\Time */
    protected $start;
    /** @var \Spatie\OpeningHours\Time */
    protected $end;

    /**
     * TimeRange constructor.
     * @param Time $start
     * @param Time $end
     */
    protected function __construct(Time $start, Time $end) {
        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @param string $string
     * @return static
     * @throws Exceptions\InvalidTimeString
     * @throws InvalidTimeRangeString
     */
    public static function fromString($string) {
        $times = explode('-', $string);

        if (count($times) !== 2) {
            throw InvalidTimeRangeString::forString($string);
        }

        return new self(Time::fromString($times[0]), Time::fromString($times[1]));
    }

    /**
     * @return Time
     */
    public function start() {
        return $this->start;
    }

    /**
     * @return Time
     */
    public function end() {
        return $this->end;
    }

    /**
     * @return bool
     */
    public function spillsOverToNextDay() {
        return $this->end->isBefore($this->start);
    }

    /**
     * @param Time $time
     * @return bool
     */
    public function containsTime(Time $time) {
        if ($this->spillsOverToNextDay()) {
            if ($time->isAfter($this->start)) {
                return $time->isAfter($this->end);
            }

            return $time->isBefore($this->end);
        }

        return $time->isSameOrAfter($this->start) && $time->isBefore($this->end);
    }

    /**
     * @param TimeRange $timeRange
     * @return bool
     */
    public function overlaps(TimeRange $timeRange) {
        return $this->containsTime($timeRange->start) || $this->containsTime($timeRange->end);
    }

    /**
     * @param string $timeFormat
     * @param string $rangeFormat
     * @return string
     */
    public function format($timeFormat = 'H:i', $rangeFormat = '%s-%s') {
        return sprintf($rangeFormat, $this->start->format($timeFormat), $this->end->format($timeFormat));
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->format();
    }
}
