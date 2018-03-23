<?php

namespace Spatie\OpeningHours;

use DateTime;
use DateTimeInterface;
use Spatie\OpeningHours\Exceptions\InvalidTimeString;

class Time
{
    /** @var int */
    protected $hours;

    /** @var int */
    protected $minutes;

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

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        return self::fromString($dateTime->format('H:i'));
    }

    public function isSame(self $time): bool
    {
        return (string) $this === (string) $time;
    }

    public function isAfter(self $time): bool
    {
        if ($this->isSame($time)) {
            return false;
        }

        if ($this->hours > $time->hours) {
            return true;
        }

        return $this->hours === $time->hours && $this->minutes >= $time->minutes;
    }

    public function isBefore(self $time): bool
    {
        if ($this->isSame($time)) {
            return false;
        }

        return ! $this->isAfter($time);
    }

    public function isSameOrAfter(self $time): bool
    {
        return $this->isSame($time) || $this->isAfter($time);
    }

    public function toDateTime(DateTime $date = null): DateTime
    {
        return ($date ?? new DateTime('1970-01-01 00:00:00'))->setTime($this->hours, $this->minutes);
    }

    public function format(string $format = 'H:i'): string
    {
        return $this->toDateTime()->format($format);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
