<?php

namespace Spatie\OpeningHours\Helpers;

/**
 * Class Arr
 * @package Spatie\OpeningHours\Helpers
 */
class Arr {
    /**
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function filter(array $array, callable $callback) {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function map(array $array, callable $callback) {
        $keys = array_keys($array);

        $items = array_map($callback, $array, $keys);

        return array_combine($keys, $items);
    }

    /**
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function flatMap(array $array, callable $callback) {
        $mapped = self::map($array, $callback);

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

    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function pull(array &$array, $key, $default = null) {
        $value = isset($array[$key]) ? $array[$key] : $default;

        unset($array[$key]);

        return $value;
    }

    /**
     * @param array $array
     * @return array
     */
    public static function mirror(array $array) {
        return array_combine($array, $array);
    }

    /**
     * @param array $array
     * @return array
     */
    public static function createUniquePairs(array $array) {
        $pairs = [];

        while ($a = array_shift($array)) {
            foreach ($array as $b) {
                $pairs[] = [$a, $b];
            }
        }

        return $pairs;
    }
}
