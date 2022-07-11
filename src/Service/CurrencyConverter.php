<?php

namespace Dejurin\ExchangeRates\Service;

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\CurrencySymbols;
use Dejurin\ExchangeRates\Models\Dev;
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

        $this->parameters['base_currency'] = $this->parameters['from'];
        $this->parameters['quote_currency'] = $this->parameters['to'];

        $currency = new Currency($this->parameters, $this->parameters['to']);
        $fmt = Currency::get_fmt($this->settings['currency_format']);

        $this->parameters['decimals'] = $this->settings['decimals'];
        $this->parameters = array_merge($this->parameters, $fmt);

        $get_currencies = Currencies::get_list();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        $currency_list = array_keys($rates['data'][0]['rates']);
        $first_element = end($currency_list);
        array_pop($currency_list);
        array_unshift($currency_list, $first_element);
        $arr = [];
        $data_attr = '';

        unset($this->parameters['quote_currency']);
        $this->parameters['base_currency'] = $this->settings['base_currency'];

        foreach ($this->parameters as $key => $value) {
            if (!empty($value)) {
                $data_attr .= 'data-'.str_replace('_', '-', $key).'="'.$value.'" ';
            }
        }

        $html = '<div '
                .'class="'.self::WIDGET_SLUG.($this->parameters['border'] ? ' border' : '').'" '
                .'id="'.self::WIDGET_SLUG.$widget_number.'" '
                .$data_attr
                .'data-currencies='
                ."'";

        foreach ($currency_list as $value) {
            $arr[$value] = [
                'name' => $get_currencies[$value]['name'],
                'rate' => ('currencyrate' === $this->settings['source_id']) ? 1 / $rates['data'][0]['rates'][$value] : $rates['data'][0]['rates'][$value],
                'symbol' => CurrencySymbols::get_list($value),
            ];
        }

        $this->parameters['base_currency'] = $this->parameters['from'];
        $this->parameters['quote_currency'] = $this->parameters['to'];

        $html .= json_encode($arr);
        $html .= "'>".esc_html__('Loading...', 'exchange-rates');
        $html .= '</div>';
        $html .= Dev::caption(
            $this->parameters,
            $currency->get_date(),
            $widget_number,
            self::WIDGET_SLUG,
            $this->settings['source_id'],
            1
        );

        return $html;
    }
}
