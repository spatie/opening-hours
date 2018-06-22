<?php
/**
 * @package opening-hours
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace Spatie\OpeningHours\Exceptions;


class NotSupportedException extends Exception {
    /**
     * @param string $feature
     * @return NotSupportedException
     */
    public static function notSupported($feature) {
        return new self("Unsupported `{$feature}`.");
    }
}
