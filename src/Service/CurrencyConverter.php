<?php

namespace Dejurin\ExchangeRates\Service;

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Dev;
use Dejurin\ExchangeRates\Plugin;

class CurrencyConverter
{
    public $parameters;
    public $settings;
    public $table;

    public function get_currency_converter($widget_number)
    {
        $settings = get_option(Settings::$option_name, []);
        $this->settings = wp_parse_args($settings, Settings::get_defaults());

        $get_currencies = Currencies::get_currencies();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        $base_currency_list = array_keys($rates['data'][0]['rates']);

        $html = '<div class="currency-converter" id="currency-converter'.$widget_number.'" data-id="currency-converter'.$widget_number.'" data-base="USD" data-quote="EUR" data-amount="1" data-base-show="true" data-currencies=';
        $html .= "'";
        $arr= [];

        foreach ($base_currency_list as $value) {
            $arr[$value] = ['name' => $get_currencies[$value]['name'], 'rate' => $rates['data'][0]['rates'][$value]];
        }
        $html .= json_encode($arr);


        $html .="'></div>";



        return $html;
    }
}
