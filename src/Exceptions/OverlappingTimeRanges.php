<?php

namespace Spatie\OpeningHours\Exceptions;

class OverlappingTimeRanges extends Exception
{
    public static function forRanges(string $rangeA, string $rangeB): self
    {
        return new self("Time ranges {$rangeA} and {$rangeB} overlap.");
    }
}
