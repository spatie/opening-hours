<?php

namespace Spatie\OpeningHours;

use DateTimeInterface;
use Spatie\OpeningHours\Helpers\Arr;

/**
 * Class Day
 * @package Spatie\OpeningHours
 */
class Day {
    const MONDAY = 'monday';
    const TUESDAY = 'tuesday';
    const WEDNESDAY = 'wednesday';
    const THURSDAY = 'thursday';
    const FRIDAY = 'friday';
    const SATURDAY = 'saturday';
    const SUNDAY = 'sunday';

    /**
     * @return array
     */
    public static function days() {
        return [
            static::MONDAY,
            static::TUESDAY,
            static::WEDNESDAY,
            static::THURSDAY,
            static::FRIDAY,
            static::SATURDAY,
            static::SUNDAY,
        ];
    }

    /**
     * @param callable $callback
     * @return array
     */
    public static function mapDays(callable $callback) {
        return Arr::map(Arr::mirror(static::days()), $callback);
    }

    /**
     * @param string $day
     * @return bool
     */
    public static function isValid($day) {
        return in_array($day, static::days());
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return string
     */
    public static function onDateTime(DateTimeInterface $dateTime) {
        return static::days()[$dateTime->format('N') - 1];
    }

    /**
     * @param string $day
     * @return int
     */
    public static function toISO($day) {
        return array_search($day, static::days()) + 1;
    }
}
