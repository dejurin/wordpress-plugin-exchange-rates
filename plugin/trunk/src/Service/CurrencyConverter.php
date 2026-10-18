<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

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
    const WIDGET_SLUG = 'widget-'.Plugin::PLUGIN_SLUG.'-currency-converter';

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
        $rates = RatesData::get();
        $source_id = RatesData::get_source($rates, $this->settings['source_id']);
        $currency_list = RatesData::get_rate_codes($rates);

        if (empty($currency_list)) {
            return Dev::rates_unavailable();
        }

        $first_element = end($currency_list);
        array_pop($currency_list);
        array_unshift($currency_list, $first_element);
        $arr = [];
        $data_attr = '';

        unset($this->parameters['quote_currency']);
        $this->parameters['base_currency'] = RatesData::get_base($rates, $this->settings['base_currency']);

        foreach ($this->parameters as $key => $value) {
            if (!empty($value) && !is_array($value) && !is_object($value)) {
                $data_attr .= 'data-'.esc_attr(str_replace('_', '-', $key)).'="'.esc_attr($value).'" ';
            }
        }

        $html = '<div class="cr-'.Plugin::PLUGIN_SLUG.'"><div '
                .'class="'.self::WIDGET_SLUG.($this->parameters['border'] ? ' border' : '').'" '
                .'id="'.esc_attr(self::WIDGET_SLUG.$widget_number).'" '
                .$data_attr
                .'data-currencies='
                ."'";

        foreach ($currency_list as $value) {
            if (!isset($get_currencies[$value])) {
                continue;
            }

            $rate = RatesData::get_rate($rates, $value, 0);
            if (null === $rate) {
                continue;
            }

            $arr[$value] = [
                'name' => $get_currencies[$value]['name'],
                'rate' => ('currencyrate' === $source_id) ? 1 / $rate : $rate,
                'symbol' => CurrencySymbols::get_list($value),
            ];
        }

        if (empty($arr)) {
            return Dev::rates_unavailable();
        }

        $this->parameters['base_currency'] = $this->parameters['from'];
        $this->parameters['quote_currency'] = $this->parameters['to'];

        $html .= esc_attr(wp_json_encode($arr));
        $html .= "'>".esc_html__('Loading...', 'exchange-rates');
        $html .= '</div></div>';
        $html .= Dev::caption(
            $this->parameters,
            $currency->get_date(),
            $widget_number,
            self::WIDGET_SLUG,
            $source_id,
            1
        );

        return $html;
    }
}
