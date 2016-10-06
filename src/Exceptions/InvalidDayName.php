<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidDayName extends Exception
{
    public static function invalidDayName(string $name): self
    {
        return new self("Day `{$name}` isn't a valid day name. Valid day names are lowercase english words, e.g. `monday`, `thursday`.");
    }
}
