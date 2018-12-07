<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidTimeRangeList extends Exception
{
    public static function create(): self
    {
        return new self('The given list is not a valid list of TimeRange instance containing at least one range.');
    }
}
