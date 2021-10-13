<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidDateTimeClass extends Exception
{
    public static function forString(string $string): self
    {
        return new self("The string `{$string}` isn't a valid class implementing DateTimeInterface.");
    }
}
