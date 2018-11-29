<?php

namespace Spatie\OpeningHours\Test;

class CustomDate extends \DateTime
{
    public function foo()
    {
        return $this->format('Y-m-d H:i:s');
    }
}
