<?php

namespace Spatie\OpeningHours\Exceptions;

use DateTimeInterface;

class SearchLimitReached extends Exception
{
    public static function forDate(DateTimeInterface $dateTime): self
    {
        return new self('Search reached the limit: '.$dateTime->format('Y-m-d H:i:s.u e'));
    }
}
