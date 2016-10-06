<?php

namespace Spatie\OpeningHours\Exceptions;

use InvalidArgumentException;

class InvalidDate extends InvalidArgumentException
{
    public static function invalidDate(string $date): self
    {
        return new self("Date `{$date}` isn't a valid date. Dates should be formatted as Y-m-d, e.g. `2016-12-25`.");
    }
}
