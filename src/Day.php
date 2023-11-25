<?php

namespace Spatie\OpeningHours;

use DateTimeInterface;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
use ValueError;

enum Day: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    public static function onDateTime(DateTimeInterface $dateTime): self
    {
        return self::fromName($dateTime->format('l'));
    }

    public static function fromName(string $day): self
    {
        try {
            return self::from(strtolower($day));
        } catch (ValueError $exception) {
            throw InvalidDayName::invalidDayName($day, $exception);
        }
    }

    public function toISO(): int
    {
        return array_search($this, self::cases()) + 1;
    }
}
