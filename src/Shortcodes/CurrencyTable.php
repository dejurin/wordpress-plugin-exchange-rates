<?php

namespace Dejurin\ExchangeRates\Shortcodes;

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\CurrencyTable as Service_CurrencyTable;

class CurrencyTable
{
    public $parameters;
    public $settings;
    public $default_attr = [];

    public const BADGE_SLUG = 'shortcode-'.Plugin::PLUGIN_SLUG.'-currency-table';

    public function __construct()
    {
        add_shortcode(Plugin::PLUGIN_SLUG.'_currency-table', [$this, 'shortcode']);
        // Default
        $this->default_attr = [
            'amount' => 1,
            'base_currency' => 'USD',
            'currency_list' => ['EUR', 'GBP', 'AUD', 'JPY', 'BRL'],
            'flag_size' => 16,
            'flag_type' => 'rectangular',
            'decimals' => 4,
            'currency_format' => 3,
            'inverse' => false,
            'code' => false,
            'region' => false,
            'full_width' => false,
            'amount_active' => false,
            'after' => false,
            'base_show' => true,
            'border' => true,
            'border_hori' => false,
            'border_vert' => false,
            'table_headers_show' => true,
            'table_headers_name' => esc_html__('Currency', 'exchange-rates'),
            'table_headers_code' => esc_html__('Code', 'exchange-rates'),
            'table_headers_mid' => esc_html__('Price', 'exchange-rates'),
            'table_headers_previous_close' => esc_html__('Previous Close', 'exchange-rates'),
            'table_headers_changes' => esc_html__('Changes', 'exchange-rates'),
            'table_headers_code_show' => false,
            'table_headers_previous_close_show' => false,
            'table_headers_changes_show' => false,
            'id' => time(),
        ];
    }

    public function shortcode($attr = [])
    {
        $obj = new Service_CurrencyTable();
        $obj->parameters = (isset($attr) && is_array($attr)) ? array_merge($this->default_attr, $attr) : $this->default_attr;

        return '<div class="cr-'.Plugin::PLUGIN_SLUG.'">'.$obj->get_html_widget($obj->parameters['id']).'</div>';
    }
}
