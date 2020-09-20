<?php

namespace Spatie\OpeningHours\Helpers;

use DateTimeImmutable;
use DateTimeInterface;

trait DateTimeCopier
{
    protected function copyDateTime(DateTimeInterface $date): DateTimeInterface
    {
        return $date instanceof DateTimeImmutable ? $date : clone $date;
    }

    protected function copyAndModify(DateTimeInterface $date, string $modifier): DateTimeInterface
    {
        return $this->copyDateTime($date)->modify($modifier);
    }

    protected function yesterday(DateTimeInterface $date): DateTimeInterface
    {
        return $this->copyAndModify($date, '-1 day');
    }
}
