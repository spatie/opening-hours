<?php

namespace Spatie\OpeningHours\Helpers;

trait DataTrait
{
    /**
     * @deprecated Use ->data readonly property instead
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}
