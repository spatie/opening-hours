<?php

namespace Spatie\OpeningHours;

class Time
{
    /** @var int */
    protected $hours, $minutes;

    protected function __construct(int $hours, int $minutes)
    {
        $this->hours = $hours;
        $this->minutes = $minutes;
    }

    public static function fromString(string $string)
    {
        if (! preg_match('/^([0-1][0-9])|(2[0-4]):[0-5][0-9]$/', $string)) {
            throw new \InvalidArgumentException();
        }

        list($hours, $minutes) = explode(':', $string);

        return new self($hours, $minutes);
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return \Spatie\OpeningHours\Time
     */
    public static function fromDateTime(\DateTime $dateTime)
    {
        return static::fromString($dateTime->format('H:i'));
    }

    public function isSame(Time $time): bool
    {
        return $this->hours === $time->hours && $this->minutes === $time->minutes;
    }

    public function isAfter(Time $time): bool
    {
        if ($this->isSame($time)) {
            return false;
        }

        return $this->hours >= $time->hours && $this->minutes >= $time->minutes;
    }

    public function isSameOrAfter(Time $time): bool
    {
        return $this->isSame($time) || $this->isAfter($time);
    }

    public function isBefore(Time $time): bool
    {
        if ($this->isSame($time)) {
            return false;
        }

        return ! $this->isAfter($time);
    }

    public function __toString()
    {
        return str_pad($this->hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($this->minutes, 2, '0', STR_PAD_LEFT);
    }
}
