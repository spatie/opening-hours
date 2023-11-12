<?php

namespace Spatie\OpeningHours\Helpers;

trait DataTrait
{
    protected readonly mixed $data;

    public function getData(): mixed
    {
        return $this->data;
    }
}
