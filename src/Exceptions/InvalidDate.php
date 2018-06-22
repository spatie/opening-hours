<?php

namespace Spatie\OpeningHours\Exceptions;

class InvalidDate extends Exception {
    /**
     * @param string $date
     * @return InvalidDate
     */
    public static function invalidDate($date) {
        return new self("Date `{$date}` isn't a valid date. Dates should be formatted as Y-m-d, e.g. `2016-12-25`.");
    }
}
