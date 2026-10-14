<?php

namespace Dejurin\ExchangeRates\Shortcodes;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\CurrencyTable as Service_CurrencyTable;

class CurrencyTable
{
    public $parameters;
    public $settings;
    public $default_attr = [];

    const BADGE_SLUG = 'shortcode-'.Plugin::PLUGIN_SLUG.'-currency-table';

    public function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init()
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
            'full_width' => true,
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
            'id' => '',
        ];
    }

    private function parse_bool_attr($attr, $name, $default = false)
    {
        if (!is_array($attr)) {
            return (bool) $default;
        }

        foreach ($attr as $key => $value) {
            if (is_string($key) && strtolower($key) === $name) {
                if (is_bool($value)) {
                    return $value;
                }

                return 'false' !== strtolower(trim((string) $value));
            }

            if (is_int($key) && is_string($value) && strtolower($value) === $name) {
                return true;
            }
        }

        return (bool) $default;
    }

    private function sanitize_currency_code($code, $fallback = 'USD')
    {
        $code = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $code));

        return preg_match('/^[A-Z]{2,6}$/', $code) ? $code : $fallback;
    }

    private function sanitize_currency_list($currency_list)
    {
        $items = is_array($currency_list) ? $currency_list : explode(',', (string) $currency_list);
        $result = [];

        foreach ($items as $item) {
            $code = $this->sanitize_currency_code($item, '');
            if ('' !== $code) {
                $result[] = $code;
            }
        }

        return array_values(array_unique($result));
    }

    private function sanitize_flag_type($flag_type)
    {
        $allowed = ['none', 'rectangular', 'square', 'circular', 'emoji'];

        return in_array($flag_type, $allowed, true) ? $flag_type : 'rectangular';
    }

    private function sanitize_atts($attr)
    {
        $raw_attr = is_array($attr) ? $attr : [];
        $attr = shortcode_atts($this->default_attr, is_array($attr) ? $attr : [], Plugin::PLUGIN_SLUG.'_currency-table');
        $bool_attrs = ['inverse', 'code', 'region', 'full_width', 'amount_active', 'after', 'base_show', 'border', 'border_hori', 'border_vert', 'table_headers_show', 'table_headers_code_show', 'table_headers_previous_close_show', 'table_headers_changes_show'];

        foreach ($bool_attrs as $name) {
            $attr[$name] = $this->parse_bool_attr($raw_attr, $name, $this->default_attr[$name]);
        }

        $id = '' === (string) $attr['id'] ? substr('t-'.md5(wp_json_encode($attr)), 0, 12) : sanitize_html_class((string) $attr['id']);

        return [
            'amount' => is_numeric($attr['amount']) ? (float) $attr['amount'] : 1,
            'base_currency' => $this->sanitize_currency_code($attr['base_currency']),
            'currency_list' => $this->sanitize_currency_list($attr['currency_list']),
            'flag_size' => max(16, min(64, (int) $attr['flag_size'])),
            'flag_type' => $this->sanitize_flag_type($attr['flag_type']),
            'decimals' => max(0, min(7, (int) $attr['decimals'])),
            'currency_format' => max(0, min(7, (int) $attr['currency_format'])),
            'inverse' => $attr['inverse'],
            'code' => $attr['code'],
            'region' => $attr['region'],
            'full_width' => $attr['full_width'],
            'amount_active' => $attr['amount_active'],
            'after' => $attr['after'],
            'base_show' => $attr['base_show'],
            'border' => $attr['border'],
            'border_hori' => $attr['border_hori'],
            'border_vert' => $attr['border_vert'],
            'table_headers_show' => $attr['table_headers_show'],
            'table_headers_name' => sanitize_text_field($attr['table_headers_name']),
            'table_headers_code' => sanitize_text_field($attr['table_headers_code']),
            'table_headers_mid' => sanitize_text_field($attr['table_headers_mid']),
            'table_headers_previous_close' => sanitize_text_field($attr['table_headers_previous_close']),
            'table_headers_changes' => sanitize_text_field($attr['table_headers_changes']),
            'table_headers_code_show' => $attr['table_headers_code_show'],
            'table_headers_previous_close_show' => $attr['table_headers_previous_close_show'],
            'table_headers_changes_show' => $attr['table_headers_changes_show'],
            'id' => $id,
        ];
    }

    public function shortcode($attr = [])
    {
        $user_provided_id = is_array($attr) && isset($attr['id']) && '' !== (string) $attr['id'];
        $obj = new Service_CurrencyTable();
        $obj->parameters = $this->sanitize_atts($attr);

        static $used = [];
        if (!$user_provided_id) {
            $base_id = $obj->parameters['id'];
            if (isset($used[$base_id])) {
                ++$used[$base_id];
                $obj->parameters['id'] = $base_id.'-'.$used[$base_id];
            } else {
                $used[$base_id] = 1;
            }
        }

        return '<div class="cr-'.Plugin::PLUGIN_SLUG.'">'.$obj->get_html_widget($obj->parameters['id']).'</div>';
    }
}
