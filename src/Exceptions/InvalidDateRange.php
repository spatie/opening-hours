<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidDateRange extends Exception
{
    public static function invalidDateRange(string $entry, string $date): self
    {
        return new self("Unable to record `$entry` as it would override `$date`.");
    }
}
