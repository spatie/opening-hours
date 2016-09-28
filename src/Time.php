<?php

namespace Spatie\OpeningHours;

use DateTime;
use Spatie\OpeningHours\Exceptions\InvalidTimeString;

class Time
{
    /** @var int */
    protected $hours, $minutes;

    protected function __construct(int $hours, int $minutes)
    {
        $this->hours = $hours;
        $this->minutes = $minutes;
    }

    public static function fromString(string $string): self
    {
        if (! preg_match('/^([0-1][0-9])|(2[0-4]):[0-5][0-9]$/', $string)) {
            throw InvalidTimeString::forString($string);
        }

        list($hours, $minutes) = explode(':', $string);

        return new self($hours, $minutes);
    }

    public static function fromDateTime(DateTime $dateTime): self
    {
        return self::fromString($dateTime->format('H:i'));
    }

    public function isSame(Time $time): bool
    {
        return (string) $this === (string) $time;
    }

    public function isAfter(Time $time): bool
    {
        if ($this->isSame($time)) {
            return false;
        }

        return $this->hours >= $time->hours && $this->minutes >= $time->minutes;
    }

    public function isBefore(Time $time): bool
    {
        if ($this->isSame($time)) {
            return false;
        }

        return ! $this->isAfter($time);
    }

    public function isSameOrAfter(Time $time): bool
    {
        return $this->isSame($time) || $this->isAfter($time);
    }

    public function toDateTime(): DateTime
    {
        return new DateTime("1970-01-01 {$this}:00");
    }

    public function __toString(): string
    {
        return str_pad($this->hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($this->minutes, 2, '0', STR_PAD_LEFT);
    }
}
