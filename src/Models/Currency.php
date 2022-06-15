<?php

/*
* 0 - current day
* 1 - prev day
*/

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Currency
{
    private $parameters;
    private $base_currency;
    private $currency;
    private $rates = null;

    public function __construct($parameters, $currency)
    {
        $this->parameters = $parameters;
        $this->set_currencies($parameters['base_currency'], $currency);
    }

    public function set_currencies($base_currency, $currency)
    {
        $this->base_currency = $base_currency;
        $this->currency = $currency;

        if (is_null($this->rates)) {
            $this->rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        }

        $this->base_currency_source = $this->rates['base'];
    }

    public static function get_fmt($currency_format)
    {
        return CurrencyFormat::get_list()[$currency_format];
    }

    public static function for_format($value, $parameters, $decimals)
    {
        $fmt = self::get_fmt($parameters['currency_format']);

        return rtrim(rtrim(number_format($value, $decimals, $fmt['decimal_point'], $fmt['thousands_sep']), '0'), '.');
    }

    public function get_date()
    {
        return [
            'local_time' => $this->rates['data'][0]['local_time'],
            'put_time' => $this->rates['data'][0]['put_time'],
        ];
    }

    public function get_rate($index = 0)
    {
        $currency_rate = $this->rates['data'][$index]['rates'][$this->currency];
        $base_rate = $this->rates['data'][$index]['rates'][$this->base_currency];

        if ($this->base_currency_source === $this->base_currency) {
            return ($this->parameters['inverse']) ? $currency_rate : $base_rate / $currency_rate;
        } else {
            return ($this->parameters['inverse']) ? $currency_rate * 1 / $base_rate : (1 / $currency_rate) * $base_rate;
        }
    }

    public function get_rate_format($index = 0, $amount = true)
    {
        return self::for_format($this->get_rate($index) * ($amount ? $this->parameters['amount'] : 1), $this->parameters, $this->parameters['decimals']);
    }

    public function get_change()
    {
        if ($this->currency !== $this->base_currency) {
            $result = self::for_format(
                (($this->get_rate(0) - $this->get_rate(1)) * $this->parameters['amount']),
                $this->parameters,
                $this->parameters['decimals']
            );
            if ('0' !== $result) {
                return $result;
            }
        }
    }

    public function get_change_percentage()
    {
        if ($this->currency !== $this->base_currency) {
            $pre = '';
            $decimal = 0;
            $value = (100 - ((100 * $this->get_rate(1)) / $this->get_rate(0)));
            $value_abs = abs($value);

            if ($value_abs > 99) {
                $decimal = 0;
            } elseif ($value_abs > 9) {
                $decimal = 1;
            } elseif ($value_abs > 0) {
                $decimal = 2;
            }

            $pre = (1 === $this->get_trend()) ? '+' : '';
            $result = self::for_format($value, $this->parameters, $decimal);

            if ('0' !== $result) {
                return $pre.$result;
            }
        }
    }

    public function get_trend()
    {
        if ($this->get_rate(0) > $this->get_rate(1)) {
            return 1;
        } elseif ($this->get_rate(0) === $this->get_rate(1)) {
            return 0;
        } else {
            return -1;
        }

        return false;
    }

    public function is_available()
    {
        return
            $this->rates
            && isset($this->rates['data'][0]['rates'][$this->currency])
            && isset($this->rates['data'][1]['rates'][$this->currency])
            && isset($this->rates['data'][0]['rates'][$this->base_currency])
            && isset($this->rates['data'][1]['rates'][$this->base_currency]);
    }
}
