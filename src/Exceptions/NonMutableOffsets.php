<?php

namespace Spatie\OpeningHours\Exceptions;

class NonMutableOffsets extends Exception
{
    public static function forClass(string $className): self
    {
        return new self("Offsets of `{$className}` objects are not mutable.");
    }
}
