<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Settings
{
    public static $option_name = Plugin::PLUGIN_SLUG;

    /* default settings */
    public static function get_defaults()
    {
        return [
            'source_id' => 'ua-nbu',
            'rates_available' => false,
        ];
    }
}
