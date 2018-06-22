<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidDayName extends Exception {
    /**
     * @param string $name
     * @return InvalidDayName
     */
    public static function invalidDayName($name) {
        return new self("Day `{$name}` isn't a valid day name. Valid day names are lowercase english words, e.g. `monday`, `thursday`.");
    }
}
