<?php

use Spatie\OpeningHours\Helpers\Arr;

it('can flat and map array', function () {
    expect(
        Arr::flatMap([1, [2, [3, 4]], 5, [6]], function ($value) {
            return is_int($value) ? -$value : $value;
        })
    )->toEqual([-1, 2, [3, 4], -5, 6]);
});
