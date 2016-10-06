<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidTimeRangeString extends Exception
{
    public static function forString(string $string): self
    {
        return new self("The string `{$string}` isn't a valid time range string. A time string must be a formatted as `H:i-H:i`, e.g. `09:00-18:00`.");
    }
}
