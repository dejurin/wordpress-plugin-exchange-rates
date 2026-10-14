<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

class Tools
{
    public static function normalize_shortcode_boolean_flags($arr, $flags)
    {
        if (!is_array($arr)) {
            return [];
        }

        foreach ($arr as $key => $value) {
            if (!is_int($key)) {
                continue;
            }

            $flag = strtolower((string) $value);
            if (in_array($flag, $flags, true)) {
                $arr[$flag] = true;
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    public static function filter_keys_allowed_list($arr, $allowed)
    {
        return array_filter(
            $arr,
            function ($key) use ($allowed) {
                return in_array($key, $allowed);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
