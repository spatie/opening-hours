<?php

namespace Spatie\OpeningHours\Helpers;

trait DataTrait
{
    /** @var mixed */
    protected $data = null;

    public function getData()
    {
        return $this->data;
    }
}
