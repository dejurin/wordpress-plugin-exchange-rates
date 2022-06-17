<?php

namespace Dejurin\ExchangeRates\Service;

class Tools
{
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
