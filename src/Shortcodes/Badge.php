<?php

namespace Dejurin\ExchangeRates\Shortcodes;

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\CurrencySymbols;
use Dejurin\ExchangeRates\Models\Dev;
use Dejurin\ExchangeRates\Models\Emoji;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\Color;
use Dejurin\ExchangeRates\Service\Tools;

class Badge
{
    public $parameters;
    public $settings;
    public $default_attr = [];

    public const BADGE_SLUG = 'shortcode-'.Plugin::PLUGIN_SLUG.'-badge';

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
            'decimals' => 4,
            'flag_type' => 'emoji',
            'color' => '#eeeeee',
            'after' => false,
            'symbol' => false,
            'id' => time(),
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
            $line = sprintf(
                $img_template,
                $w,
                $h,
                sprintf('https://s.w.org/images/core/emoji/14.0.0/svg/%1$s.svg', esc_html(strtolower($emoji[$currency['flag']]))),
                $currency['flag']
            );
        } elseif ('none' !== $flag_type) {
            $line = sprintf(
                $img_template,
                $w,
                $h,
                sprintf(plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/'.$flag_type.'/%1$s.svg', esc_html(strtolower($currency['flag']))),
                $currency['flag']
            );
        }

        return $line;
    }

    public function shortcode($attr)
    {
        $caption = null;
        $currency = null;
        $result = '';
        $err = true;

        $settings = get_option(Settings::$option_name, []);
        $this->settings = wp_parse_args($settings, Settings::get_defaults());

        if (is_array($attr)) {
            $attr = array_merge($this->default_attr, $attr);
        } else {
            $attr = $this->default_attr;
        }

        $attr['base_show'] = false !== $attr['base_show'];
        $attr['inverse'] = false !== $attr['inverse'];

        $rgb = Color::HTMLToRGB($attr['color']);
        $hsl = Color::RGBToHSL($rgb);

        if (array_key_exists('currency_list', $attr) && !empty($attr['currency_list'])) {
            $currency_list = explode(',', $attr['currency_list']);
            $get_currencies = Currencies::get_list();

            if (is_array($currency_list) && !empty($currency_list)) {
                $parameters = Tools::filter_keys_allowed_list($attr, ['source_id', 'base_currency', 'amount', 'base_show', 'code', 'currency_format', 'inverse', 'decimals', 'symbol', 'after', 'color', 'flag_type']);
                $parameters['reverse'] = 'currencyrate' === $this->settings['source_id'];

                foreach ($currency_list as $code) {
                    $currency = new Currency($parameters, $code);

                    if ($currency->is_available() && isset($get_currencies[$code])) {
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


                        $base_currency = ($parameters['base_show']) ? ((isset($parameters['code'])) ? $parameters['base_currency'] : $get_currencies[$parameters['base_currency']]['name']).'/' : '';
                        $template = '<div class="badge-leaders"><span style="background-color:%1$s!important;color:%2$s!important">%3$s'.esc_html($base_currency).'%4$s</span><span style="background-color:%1$s!important;color:%2$s!important">%5$s%6$s%7$s</span></div>';
                        $result .= sprintf(
                                $template,
                                esc_html($parameters['color']),
                                $hsl->lightness > 200 ? '#333333' : '#ffffff',
                                $this->img($parameters['flag_type'], $get_currency),
                                (isset($parameters['code']) ? esc_html($code) : esc_html($get_currency['name'])),
                                esc_html($pre),
                                esc_html($currency->get_rate_format(0, true, true)),
                                esc_html($after)
                            );
                        $err = false;
                    }
                }
            }
        }

        if ($currency) {
            $caption = Dev::caption(
                $parameters,
                $currency->get_date(),
                $attr['id'],
                self::BADGE_SLUG,
                $currency->get_source_id(),
                2
            );
        } else {
            $err = true;
        }

        if ($err) {
            return '<b>'.Plugin::NAME.'</b> '.esc_html__('Error: Check parameters of widget or shortcode. Probably you changed the source of currency rates, where there was not the desired currency that you selected before.', 'exchange-rates');
        }

        return '<div class="cr-'.Plugin::PLUGIN_SLUG.' shortcode-'.Plugin::PLUGIN_SLUG.'-badge">'.esc_html($result.$caption).'</div>';
    }
}
