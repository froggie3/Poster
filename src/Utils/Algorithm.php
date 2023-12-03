<?php

declare(strict_types=1);

namespace App\Utils;

class Algorithm
{
    /**
     * Memoization of function (was to be used)
     */
    static function memoized(callable $function)
    {
        return function () use ($function) {
            static $cache = array();
            $args = func_get_args();
            $key = serialize($args);
            if (!isset($cache[$key])) {
                $cache[$key] = call_user_func_array($function, $args);
            }
            return $cache[$key];
        };
    }

    /**
     * clamps & returns the given number into the range 0 - 2
     */
    static function clamp(int $x, int $lo, int $hi): int
    {
        return max($lo, min($x, $hi));
    }
}
