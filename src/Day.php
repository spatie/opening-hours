<?php

namespace Spatie\OpeningHours;

use DateTimeInterface;
use Spatie\OpeningHours\Helpers\Arr;

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
        return Arr::map(Arr::mirror(static::days()), $callback);
    }

    public static function isValid(string $day): bool
    {
        return in_array($day, static::days());
    }

    public static function onDateTime(DateTimeInterface $dateTime): string
    {
        return static::days()[$dateTime->format('N') - 1];
    }

    public static function toISO(string $day): int
    {
        return array_search($day, static::days()) + 1;
    }
}
