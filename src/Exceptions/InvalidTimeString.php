<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidTimeString extends Exception
{
    public static function forString(string $string): self
    {
        return new self("The string `{$string}` isn't a valid time string. A time string must be a formatted as `H:i`, e.g. `06:00`, `18:00`.");
    }
}
