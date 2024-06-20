<?php

namespace Spatie\OpeningHours\Test;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\OpeningHours\Helpers\Arr;

class ArrTest extends TestCase
{
    #[Test]
    public function it_can_flat_and_map_array()
    {
        $this->assertSame([-1, 2, [3, 4], -5, 6], Arr::flatMap([1, [2, [3, 4]], 5, [6]], function ($value) {
            return is_int($value) ? -$value : $value;
        }));
    }
}
