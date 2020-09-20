<?php

namespace Spatie\OpeningHours;

interface TimeDataContainer
{
    public const TIME_FORMAT = 'H:i';

    public static function fromString(string $string): self;

    public function __toString(): string;
}
