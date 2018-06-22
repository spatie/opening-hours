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

    /**
     * Time constructor.
     * @param $hours
     * @param $minutes
     */
    protected function __construct($hours, $minutes)
    {
        $this->hours = $hours;
        $this->minutes = $minutes;
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return static
     * @throws InvalidTimeString
     */
    public static function fromDateTime(DateTimeInterface $dateTime)
    {
        return self::fromString($dateTime->format('H:i'));
    }

    /**
     * @param string $string
     * @return static
     * @throws InvalidTimeString
     */
    public static function fromString($string)
    {
        if (!preg_match('/^([0-1][0-9])|(2[0-4]):[0-5][0-9]$/', $string)) {
            throw InvalidTimeString::forString($string);
        }

        list($hours, $minutes) = explode(':', $string);

        return new self($hours, $minutes);
    }

    /**
     * @param Time $time
     * @return bool
     */
    public function isBefore(Time $time)
    {
        if ($this->isSame($time)) {
            return false;
        }

        return !$this->isAfter($time);
    }

    /**
     * @param Time $time
     * @return bool
     */
    public function isSame(Time $time)
    {
        return (string)$this === (string)$time;
    }

    /**
     * @param Time $time
     * @return bool
     */
    public function isAfter(Time $time)
    {
        if ($this->isSame($time)) {
            return false;
        }

        if ($this->hours > $time->hours) {
            return true;
        }

        return $this->hours === $time->hours && $this->minutes >= $time->minutes;
    }

    /**
     * @param Time $time
     * @return bool
     */
    public function isSameOrAfter(Time $time)
    {
        return $this->isSame($time) || $this->isAfter($time);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->format();
    }

    /**
     * @param string $format
     * @return string
     */
    public function format($format = 'H:i')
    {
        return $this->toDateTime()->format($format);
    }

    /**
     * @param DateTime|null $date
     * @return DateTime|false
     */
    public function toDateTime(DateTime $date = null)
    {
        return ($date ?: new DateTime('1970-01-01 00:00:00'))->setTime($this->hours, $this->minutes);
    }
}
