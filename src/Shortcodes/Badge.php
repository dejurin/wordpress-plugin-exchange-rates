<?php

namespace Dejurin\ExchangeRates\Shortcodes;


use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Service\Tools;
use Dejurin\ExchangeRates\Models\Emoji;
use Dejurin\ExchangeRates\Plugin;

class Badge
{

    public $default_attr = [
        'amount' => 1,
        'base_currency' => 'USD',
        'currency_list' => 'EUR',
        'base_currency_show' => true,
        'inverse'=> 1,
        'decimals' => 4,
        'flag_type' => 'emoji',
    ];

    public function __construct() {
        add_shortcode(Plugin::PLUGIN_SLUG . '-badge', [$this, 'shortcode']);
    }

    protected function img($flag_type, $currency) {
        $w = 24;
        $h = 24;
        $img_template = '<img loading="lazy" src="%1$s" width="%2$s" height="%3$s" alt="%4$s" />';
        $img_src_template = plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/'.$flag_type.'/%1$s.svg';


        if ('emoji' === $flag_type) {
            $emoji = Emoji::get_list();
            $line = $emoji[$currency['flag']];
        } else if ('none' !== $flag_type) {
            $line = sprintf(
                $img_template,
                sprintf($img_src_template, strtolower($currency['flag'])),
                $w,
                $h,
                'title'
            );
        }

        return $line;
    }

    public function shortcode($attr) {
        $settings = get_option(Settings::$option_name, []);

        if (is_array($attr)) {
            $attr = array_merge($this->default_attr, $attr);
        } else {
            $attr = $this->default_attr;
        }

        $result = __(Plugin::NAME.': error plugin. Check the attributes of the shortcode.', Plugin::PLUGIN_SLUG);

        if (array_key_exists('currency_list', $attr) && !empty($attr['currency_list'])) {
            $currency_list = explode(',', $attr['currency_list']);
            $result = '<div class="widget-exchange-rates-shortcode-badge">';
        
            if (is_array($currency_list) && !empty($currency_list)) {
                $parameters = Tools::filter_keys_allowed_list($attr, ['base_currency', 'amount', 'currency_format', 'inverse', 'decimals']);
                $get_currencies = Currencies::get_currencies();
        
                foreach ($currency_list as $code) {
                    if ($code !== $attr['base_currency']) {
                        $get_currency = $get_currencies[$code];
                        $currency = new Currency($parameters, $code);
                        $base_currency = ($attr['show_base_currency']) ? $attr['base_currency'] . '/' : '';
                        $template = '<div class="badge-leaders"><span>%1$s&nbsp;'.$base_currency.'%2$s</span><span>%3$s</span></div>';
                        $result .= sprintf(
                            $template,
                            $this->img($attr['flag_type'], $get_currency),
                            $code,
                            $currency->get_rate_format(0, true, true)
                        );
                    }
                }
            }
        }

        return $result.'</div>';
    }
}