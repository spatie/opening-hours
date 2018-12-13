<?php

namespace Spatie\OpeningHours\Helpers;

class Arr
{
    public static function filter(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function map(array $array, callable $callback): array
    {
        $keys = array_keys($array);

        $items = array_map($callback, $array, $keys);

        return array_combine($keys, $items);
    }

    public static function flatMap(array $array, callable $callback): array
    {
        $mapped = static::map($array, $callback);

        $flattened = [];

        foreach ($mapped as $item) {
            if (is_array($item)) {
                $flattened = array_merge($flattened, $item);
            } else {
                $flattened[] = $item;
            }
        }

        return $flattened;
    }

    public static function pull(&$array, $key, $default = null)
    {
        $value = $array[$key] ?? $default;

        unset($array[$key]);

        return $value;
    }

    public static function mirror(array $array): array
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
