<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Checkbox
{
    public static function get_list()
    {
        return [
            'full_width' => esc_html__('Full-width table', 'exchange-rates'),
            'amount_active' => esc_html__('Active amount input', 'exchange-rates'),
            // 'border_hori' => esc_html__('Border horizontal', 'exchange-rates'),
            // 'border_vert' => esc_html__('Border vertical', 'exchange-rates'),
            'base_show' => esc_html__('Show base currency', 'exchange-rates'),
            'inverse' => esc_html__('Inverse (currencies will be calc based on the amount of the base currency)', 'exchange-rates'),
            'code' => esc_html__('Show currency code only', 'exchange-rates'),
            'symbol' => esc_html__('Show symbol of currency $100', 'exchange-rates'),
            'after' => esc_html__('Symbol after amount - 100$ (or unchecked $100)', 'exchange-rates'),
            'border' => esc_html__('Border box', 'exchange-rates'),
            'region' => esc_html__('Show currency region (country)', 'exchange-rates'),
        ];
    }
}
