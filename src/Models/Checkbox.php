<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Checkbox
{
    public static function get_list()
    {
        return [
            'border' => __('Table border', Plugin::PLUGIN_SLUG),
            'full_width' => __('Full-width table', Plugin::PLUGIN_SLUG),
            'amount_active' => __('Active amount input.', Plugin::PLUGIN_SLUG),
            'base_show' => __('Show base currency.', Plugin::PLUGIN_SLUG),
            'inverse' => __('Inverse (currencies will be calc based on the amount of the base currency).', Plugin::PLUGIN_SLUG),
            'code' => __('Show currency code only.', Plugin::PLUGIN_SLUG),
            'region' => __('Show currency region (country).', Plugin::PLUGIN_SLUG),
        ];
    }
}
