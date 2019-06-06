<?php

namespace Spatie\OpeningHours\Exceptions;

class MaximumLimitExceeded extends Exception
{
    public static function forString(string $string): self
    {
        return new self($string);
    }
}
