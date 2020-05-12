<?php

namespace Spatie\OpeningHours\Exceptions;

use InvalidArgumentException;

class InvalidTimezone extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('Invalid Timezone');
    }
}
