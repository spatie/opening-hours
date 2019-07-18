<?php

namespace Spatie\OpeningHours\Helpers;

use DateTimeImmutable;
use DateTimeInterface;

trait DateTimeCopier
{
    /**
     * @param DateTimeInterface $date
     *
     * @return \DateTime|\DateTimeImmutable
     */
    protected function copyDateTime(DateTimeInterface $date): DateTimeInterface
    {
        return $date instanceof DateTimeImmutable ? $date : clone $date;
    }
}
