<?php

namespace Spatie\OpeningHours;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Spatie\OpeningHours\Exceptions\InvalidTimeString;
use Spatie\OpeningHours\Helpers\DataTrait;
use Spatie\OpeningHours\Helpers\DateTimeCopier;

class Time
{
    use DataTrait, DateTimeCopier;

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
        if (! preg_match('/^(([0-1][0-9]|2[0-3]):[0-5][0-9]|24:00)$/', $string)) {
            throw InvalidTimeString::forString($string);
        }

        list($hours, $minutes) = explode(':', $string);

        return new self($hours, $minutes);
    }

    public function hours(): int
    {
        return $this->hours;
    }

    public function minutes(): int
    {
        return $this->minutes;
    }

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        return static::fromString($dateTime->format('H:i'));
    }

    public function isSame(self $time): bool
    {
        return $this->hours === $time->hours && $this->minutes === $time->minutes;
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

    public function diff(self $time): \DateInterval
    {
        return $this->toDateTime()->diff($time->toDateTime());
    }

    public function toDateTime(DateTimeInterface $date = null): DateTimeInterface
    {
        $date = $date ? $this->copyDateTime($date) : new DateTime('1970-01-01 00:00:00');

        return $date->setTime($this->hours, $this->minutes);
    }

    public function format(string $format = 'H:i', $timezone = null): string
    {
        $date = $timezone
            ? new DateTime('1970-01-01 00:00:00', $timezone instanceof DateTimeZone
                ? $timezone
                : new DateTimeZone($timezone)
            )
            : null;

        if ($format === 'H:i' && $this->hours === 24 && $this->minutes === 0) {
            return '24:00';
        }

        return $this->toDateTime($date)->format($format);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
