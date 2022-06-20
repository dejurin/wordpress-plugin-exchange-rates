<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class TableColumns
{
    public static function get_list()
    {
        return [
            'name' => __('Currency col:', Plugin::PLUGIN_SLUG),
            'code' => __('Code col:', Plugin::PLUGIN_SLUG),
            'mid' => __('Price col:', Plugin::PLUGIN_SLUG),
            'previous_close' => __('Previous Close col:', Plugin::PLUGIN_SLUG),
            'changes' => __('Changes col:', Plugin::PLUGIN_SLUG),
        ];
    }
}