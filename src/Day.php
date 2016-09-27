<?php

namespace Spatie\OpeningHours;

use DateTime;

class Day
{
    const MONDAY = 'monday';
    const TUESDAY = 'tuesday';
    const WEDNESDAY = 'wednesday';
    const THURSDAY = 'thursday';
    const FRIDAY = 'friday';
    const SATURDAY = 'saturday';
    const SUNDAY = 'sunday';

    public static function days(): array
    {
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

    public static function mapDays(callable $callback): array
    {
        return array_map($callback, array_combine(static::days(), static::days()));
    }

    public static function isValid(string $day): bool
    {
        return in_array($day, static::days());
    }

    /**
     *
     * @param \DateTime $dateTime
     *
     * @return static
     */
    public static function forDateTime(DateTime $dateTime)
    {
        return static::days()[$dateTime->format('N') - 1];
    }
}
