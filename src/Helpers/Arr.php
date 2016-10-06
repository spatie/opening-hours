<?php

namespace Spatie\OpeningHours\Helpers;

class Arr
{
    public static function map(array $array, callable $callback)
    {
        $keys = array_keys($array);

        $items = array_map($callback, $array, $keys);

        return array_combine($keys, $items);
    }

    public static function pull(&$array, $key, $default = null)
    {
        $value = $array[$key] ?? $default;

        unset($array[$key]);

        return $value;
    }

    public static function mirror(array $array)
    {
        return array_combine($array, $array);
    }

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
