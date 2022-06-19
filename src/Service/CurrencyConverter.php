<?php

namespace Dejurin\ExchangeRates\Service;


use Dejurin\ExchangeRates\Models\CurrencySymbols;
use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\Settings;

use Dejurin\ExchangeRates\Plugin;

class CurrencyConverter
{
    public $parameters;
    public $settings;

    public const WIDGET_SLUG = 'widget-'.Plugin::PLUGIN_SLUG.'-currency-converter';

    public function get_html_widget($widget_number)
    {
        $settings = get_option(Settings::$option_name, []);
        $this->settings = wp_parse_args($settings, Settings::get_defaults());
        $fmt = Currency::get_fmt($this->settings['currency_format']);

        $this->parameters['decimals'] = $this->settings['decimals'];
        $this->parameters = array_merge($this->parameters, $fmt);

        $get_currencies = Currencies::get_currencies();
        $get_symbols = CurrencySymbols::$get_list;
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        $base_currency_list = array_keys($rates['data'][0]['rates']);
        $arr = [];
        $data_attr = '';

        foreach($this->parameters as $key => $value) {
            if (!empty($value)) {
                $data_attr .= 'data-' . str_replace('_', '-', $key) . '="' . $value .'" ';
            }
        }

        $html = '<div '
                .'class="'.self::WIDGET_SLUG.'" '
                .'id="currency-converter'.$widget_number.'" '
                . $data_attr
                .'data-currencies='
                ."'";

        foreach ($base_currency_list as $value) {
            $arr[$value] = [
                'name' => $get_currencies[$value]['name'],
                'rate' => $rates['data'][0]['rates'][$value],
                'symbol' => (isset($get_symbols[$value]) ? $get_symbols[$value] : $value),
            ];
        }

        $html .= json_encode($arr);
        $html .= "'>".__('Loading...', Plugin::PLUGIN_SLUG)."</div>";

        return $html;
    }
}