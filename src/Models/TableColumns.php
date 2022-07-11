<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class TableColumns
{
    public static function get_list()
    {
        return [
            'name' => esc_html__('Currency col:', 'exchange-rates'),
            'code' => esc_html__('Code col:', 'exchange-rates'),
            'mid' => esc_html__('Price col:', 'exchange-rates'),
            'previous_close' => esc_html__('Previous Close col:', 'exchange-rates'),
            'changes' => esc_html__('Changes col:', 'exchange-rates'),
        ];
    }
}
