<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Checkbox
{
    public static function get_list()
    {
        return [
            'full_width' => __('Full-width table', Plugin::PLUGIN_SLUG),
            'amount_active' => __('Active amount input', Plugin::PLUGIN_SLUG),
            //'border_hori' => __('Border horizontal', Plugin::PLUGIN_SLUG),
            //'border_vert' => __('Border vertical', Plugin::PLUGIN_SLUG),
            'base_show' => __('Show base currency', Plugin::PLUGIN_SLUG),
            'inverse' => __('Inverse (currencies will be calc based on the amount of the base currency)', Plugin::PLUGIN_SLUG),
            'code' => __('Show currency code only', Plugin::PLUGIN_SLUG),
            'symbol' => __('Show symbol of currency', Plugin::PLUGIN_SLUG),
            'after' => __('Symbol format: checked 100$ or unchecked $100', Plugin::PLUGIN_SLUG),
            'border' => __('Border box', Plugin::PLUGIN_SLUG),
            'region' => __('Show currency region (country)', Plugin::PLUGIN_SLUG),
        ];
    }
}
