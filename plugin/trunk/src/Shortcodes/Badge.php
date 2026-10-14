<?php

namespace Dejurin\ExchangeRates\Shortcodes;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\CurrencySymbols;
use Dejurin\ExchangeRates\Models\Dev;
use Dejurin\ExchangeRates\Models\Emoji;
use Dejurin\ExchangeRates\Models\Flags;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\Color;
use Dejurin\ExchangeRates\Service\Tools;

class Badge
{
    public $parameters;
    public $settings;
    public $default_attr = [];

    const BADGE_SLUG = 'shortcode-'.Plugin::PLUGIN_SLUG.'-badge';

    public function __construct()
    {
        add_shortcode(Plugin::PLUGIN_SLUG.'_badge', [$this, 'shortcode']);
        // Default
        $this->default_attr = [
            'amount' => 1,
            'base_currency' => 'USD',
            'currency_list' => 'EUR',
            'base_show' => false,
            'inverse' => false,
            'code' => false,
            'decimals' => 4,
            'flag_type' => 'emoji',
            'color' => '#eeeeee',
            'after' => false,
            'symbol' => false,
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
        $allowed = [];
        foreach (Flags::get_types() as $item) {
            $allowed[] = $item['value'];
        }

        return in_array($flag_type, $allowed, true) ? $flag_type : 'emoji';
    }

    private function sanitize_badge_color($color)
    {
        $color = trim((string) $color);

        if ('' === $color) {
            return $this->default_attr['color'];
        }

        $hex = sanitize_hex_color($color);

        if ($hex) {
            return $hex;
        }

        if (preg_match('/^[a-zA-Z]+$/', $color)) {
            return strtolower($color);
        }

        if (preg_match('/^rgba?\(\s*(\d{1,3}%?\s*,\s*){2}\d{1,3}%?(\s*,\s*(0|1|0?\.\d+|\d{1,3}%))?\s*\)$/i', $color)) {
            return $color;
        }

        if (preg_match('/^hsla?\(\s*-?\d+(deg|rad|turn)?\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%(\s*,\s*(0|1|0?\.\d+|\d{1,3}%))?\s*\)$/i', $color)) {
            return $color;
        }

        if (preg_match('/^var\(\s*--[A-Za-z0-9_-]+\s*\)$/', $color)) {
            return $color;
        }

        return $this->default_attr['color'];
    }

    private function sanitize_atts($attr)
    {
        $raw_attr = is_array($attr) ? $attr : [];
        $attr = shortcode_atts($this->default_attr, is_array($attr) ? $attr : [], Plugin::PLUGIN_SLUG.'_badge');
        $attr['base_show'] = $this->parse_bool_attr($raw_attr, 'base_show', $this->default_attr['base_show']);
        $attr['inverse'] = $this->parse_bool_attr($raw_attr, 'inverse', $this->default_attr['inverse']);
        $attr['code'] = $this->parse_bool_attr($raw_attr, 'code', $this->default_attr['code']);
        $attr['after'] = $this->parse_bool_attr($raw_attr, 'after', $this->default_attr['after']);
        $attr['symbol'] = $this->parse_bool_attr($raw_attr, 'symbol', $this->default_attr['symbol']);
        $color = $this->sanitize_badge_color($attr['color']);

        $id = '' === (string) $attr['id'] ? substr('b-'.md5(wp_json_encode($attr)), 0, 12) : sanitize_html_class((string) $attr['id']);

        return [
            'amount' => is_numeric($attr['amount']) ? (float) $attr['amount'] : 1,
            'base_currency' => $this->sanitize_currency_code($attr['base_currency']),
            'currency_list' => $this->sanitize_currency_list($attr['currency_list']),
            'base_show' => $attr['base_show'],
            'inverse' => $attr['inverse'],
            'code' => $attr['code'],
            'decimals' => max(0, min(7, (int) $attr['decimals'])),
            'flag_type' => $this->sanitize_flag_type($attr['flag_type']),
            'color' => $color,
            'after' => $attr['after'],
            'symbol' => $attr['symbol'],
            'id' => $id,
        ];
    }

    protected function img($flag_type, $currency)
    {
        $w = 20;
        $h = ('rectangular' === $flag_type) ? ($w / 4 * 3) : $w;

        $img_template = '<img loading="lazy" style="width:%1$spx!important;height:%2$spx!important" src="%3$s" alt="%4$s" />&nbsp;';
        $line = '';

        if ('emoji' === $flag_type) {
            $emoji = Emoji::get_list();
            if (!isset($emoji[$currency['flag']])) {
                return '';
            }

            $line = sprintf(
                $img_template,
                $w,
                $h,
                esc_url(sprintf('https://s.w.org/images/core/emoji/14.0.0/svg/%1$s.svg', strtolower($emoji[$currency['flag']]))),
                esc_attr($currency['flag'])
            );
        } elseif ('none' !== $flag_type) {
            $line = sprintf(
                $img_template,
                $w,
                $h,
                esc_url(sprintf(plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/'.rawurlencode($flag_type).'/%1$s.svg', strtolower($currency['flag']))),
                esc_attr($currency['flag'])
            );
        }

        return $line;
    }

    public function shortcode($attr)
    {
        $caption = null;
        $currency = null;
        $caption_currency = null;
        $result = '';
        $err = true;
        $user_provided_id = is_array($attr) && isset($attr['id']) && '' !== (string) $attr['id'];

        $settings = get_option(Settings::$option_name, []);
        $this->settings = wp_parse_args($settings, Settings::get_defaults());

        $attr = $this->sanitize_atts($attr);

        static $used = [];
        if (!$user_provided_id) {
            $base_id = $attr['id'];
            if (isset($used[$base_id])) {
                ++$used[$base_id];
                $attr['id'] = $base_id.'-'.$used[$base_id];
            } else {
                $used[$base_id] = 1;
            }
        }

        $rgb = Color::HTMLToRGB($attr['color']);
        $hsl = Color::RGBToHSL($rgb);

        if (array_key_exists('currency_list', $attr) && !empty($attr['currency_list'])) {
            $currency_list = $attr['currency_list'];
            $get_currencies = Currencies::get_list();

            if (is_array($currency_list) && !empty($currency_list)) {
                $parameters = Tools::filter_keys_allowed_list($attr, ['source_id', 'base_currency', 'amount', 'base_show', 'code', 'currency_format', 'inverse', 'decimals', 'symbol', 'after', 'color', 'flag_type']);
                $parameters['reverse'] = 'currencyrate' === $this->settings['source_id'];

                foreach ($currency_list as $code) {
                    if (!isset($get_currencies[$code])) {
                        continue;
                    }

                    $currency = new Currency($parameters, $code);

                    if ($currency->is_available() && isset($get_currencies[$code])) {
                        $caption_currency = $currency;
                        $get_currency = $get_currencies[$code];

                        $symbol = '';
                        if ($parameters['symbol']) {
                            $symbol = CurrencySymbols::get_list($parameters['inverse'] ? $parameters['base_currency'] : $code);
                        }
                        
                        $pre = $symbol;
                        $after = '';

                        if ($parameters['after']) {
                            $after = $pre;
                            $pre = '';
                        }


                        $base_currency = '';
                        if ($parameters['base_show'] && isset($get_currencies[$parameters['base_currency']])) {
                            $base_currency = ($parameters['code'] ? $parameters['base_currency'] : $get_currencies[$parameters['base_currency']]['name']).'/';
                        }

                        $template = '<div class="badge-leaders"><span style="background-color:%1$s!important;color:%2$s!important">%3$s'.esc_html($base_currency).'%4$s</span><span style="background-color:%1$s!important;color:%2$s!important">%5$s%6$s%7$s</span></div>';
                        $result .= sprintf(
                                $template,
                            esc_attr($parameters['color']),
                                $hsl->lightness > 200 ? '#333333' : '#ffffff',
                                $this->img($parameters['flag_type'], $get_currency),
                                ($parameters['code'] ? esc_html($code) : esc_html($get_currency['name'])),
                                esc_html($pre),
                                esc_html($currency->get_rate_format(0, true, true)),
                                esc_html($after)
                            );
                        $err = false;
                    }
                }
            }
        }

        if ($caption_currency) {
            $caption = Dev::caption(
                $parameters,
                $caption_currency->get_date(),
                $attr['id'],
                self::BADGE_SLUG,
                $caption_currency->get_source_id(),
                2
            );
        } else {
            $err = true;
        }

        if ($err) {
            return '<b>'.Plugin::NAME.'</b> '.esc_html__('Error: Check parameters of widget or shortcode. Probably you changed the source of currency rates, where there was not the desired currency that you selected before.', 'exchange-rates');
        }

        return '<div class="cr-'.Plugin::PLUGIN_SLUG.' shortcode-'.Plugin::PLUGIN_SLUG.'-badge">'.$result.$caption.'</div>';
    }
}
