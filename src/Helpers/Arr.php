<?php

namespace Spatie\OpeningHours\Helpers;

class Arr
{
    public static function createUniquePairs(array $array): array
    {
        $pairs = [];

        while ($a = array_shift($array)) {
            foreach ($array as $b) {
                $pairs[] = [$a, $b];
            }
        }

        return $pairs;
    }
}
