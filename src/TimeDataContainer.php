<?php

namespace Spatie\OpeningHours;

interface TimeDataContainer
{
    public const TIME_FORMAT = 'H:i';
    public const MIDNIGHT = '00:00'; // Midnight represented in the TIME_FORMAT

    public static function fromString(string $string): self;

    public function __toString(): string;
}
