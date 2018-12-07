<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidTimeRangeArray extends Exception
{
    public static function create(): self
    {
        return new self('TimeRange array definition must at least contains an "hours" property.');
    }
}
