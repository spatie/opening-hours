<?php

namespace Spatie\OpeningHours;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class PreciseTime extends Time
{
    /** @var DateTimeInterface */
    protected $dateTime;

    protected function __construct(DateTimeInterface $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public static function fromString(string $string): parent
    {
        return self::fromDateTime(new DateTimeImmutable($string));
    }

    public function hours(): int
    {
        return (int) $this->dateTime->format('G');
    }

    public function minutes(): int
    {
        return (int) $this->dateTime->format('i');
    }

    public static function fromDateTime(DateTimeInterface $dateTime): parent
    {
        return new self($dateTime);
    }

    public function isSame(parent $time): bool
    {
        return $this->format('H:i:s.u') === $time->format('H:i:s.u');
    }

    public function isAfter(parent $time): bool
    {
        return $this->format('H:i:s.u') > $time->format('H:i:s.u');
    }

    public function isBefore(parent $time): bool
    {
        return $this->format('H:i:s.u') < $time->format('H:i:s.u');
    }

    public function isSameOrAfter(parent $time): bool
    {
        return $this->format('H:i:s.u') >= $time->format('H:i:s.u');
    }

    public function diff(parent $time): \DateInterval
    {
        return $this->toDateTime()->diff($time->toDateTime());
    }

    public function toDateTime(DateTimeInterface $date = null): DateTimeInterface
    {
        return $date
            ? $this->copyDateTime($date)->modify($this->format('H:i:s.u'))
            : $this->copyDateTime($this->dateTime);
    }

    public function format(string $format = 'H:i', $timezone = null): string
    {
        $date = $timezone
            ? $this->copyDateTime($this->dateTime)->setTimezone($timezone instanceof DateTimeZone
                ? $timezone
                : new DateTimeZone($timezone)
            )
            : $this->dateTime;

        return $date->format($format);
    }
}
