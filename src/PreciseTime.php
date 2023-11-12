<?php

namespace Spatie\OpeningHours;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

readonly class PreciseTime extends Time
{
    protected DateTimeInterface $dateTime;

    protected function __construct(DateTimeInterface $dateTime, mixed $data = null)
    {
        $this->dateTime = $dateTime;
        parent::__construct(0, 0, $data);
    }

    public static function fromString(string $string, mixed $data = null, ?DateTimeInterface $date = null): parent
    {
        if ($date !== null) {
            throw new InvalidArgumentException(static::class . ' does not support date reference point');
        }

        return self::fromDateTime(new DateTimeImmutable($string), $data);
    }

    public function hours(): int
    {
        return (int) $this->dateTime->format('G');
    }

    public function minutes(): int
    {
        return (int) $this->dateTime->format('i');
    }

    public static function fromDateTime(DateTimeInterface $dateTime, mixed $data = null): parent
    {
        return new self($dateTime, $data);
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

    public function diff(parent $time): DateInterval
    {
        return $this->toDateTime()->diff($time->toDateTime());
    }

    public function toDateTime(DateTimeInterface $date = null): DateTimeInterface
    {
        return $date
            ? $this->copyDateTime($date)->modify($this->format('H:i:s.u'))
            : $this->copyDateTime($this->dateTime);
    }

    public function format(string $format = 'H:i', DateTimeZone|string|null $timezone = null): string
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
