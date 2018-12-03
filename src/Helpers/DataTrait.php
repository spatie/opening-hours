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

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
