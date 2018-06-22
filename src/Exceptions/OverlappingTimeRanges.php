<?php

namespace Spatie\OpeningHours\Exceptions;

class OverlappingTimeRanges extends Exception
{
    /**
     * @param string $rangeA
     * @param string $rangeB
     * @return OverlappingTimeRanges
     */
    public static function forRanges($rangeA, $rangeB)
    {
        return new self("Time ranges {$rangeA} and {$rangeB} overlap.");
    }
}
