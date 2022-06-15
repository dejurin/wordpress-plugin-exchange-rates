<?php

namespace Dejurin\ExchangeRates\Shortcodes;


use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;

class Badge
{

    public $default_attr = [
        'amout' => 1,
        'base_currency' => 'USD',
        'currency_list' => 'EUR,GBP',
        'show_base_currency' => true,
        ''
    ];

    public function __construct(){
        add_shortcode(Plugin::PLUGIN_SLUG . '-badge', [$this, 'shortcode']);
    }

    public function shortcode($attr) {
        $settings = get_option(Settings::$option_name, []);

        var_dump($settings);


        if (is_array($attr)) {
            $attr = array_merge($this->default_attr, $attr);
        } else {
            $attr = $this->default_attr;
        }

        $currency_list = explode(',',$attr['currency_list']);
        $result = '<div class="widget-exchange-rates-shortcode-badge">';

        $parameters = [
            'base_currency' => 'USD',
            'currency_format' => 3,
            'decimal_point' => '.',
            'thousands_sep' => ',',
            'inverse'=>1,
            'amount' => 1,
            'decimals' => 4,
        ];

        foreach ($currency_list as $code) {
            if ($code !== $attr['base_currency']) {
                $currency = new Currency($parameters, $code);
                $base_currency = ($attr['show_base_currency']) ? $attr['base_currency'] . '/' : '';
                $template = '<div class="badge-leaders"><span>'.$base_currency.'%1$s</span><span>%2$s</span></div>';
                $result .= sprintf(
                    $template,
                    $code,
                    $currency->get_rate(0)
                );
            }
        }

        return $result.'</div>';

    }
}