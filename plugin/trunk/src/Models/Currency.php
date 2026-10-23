<?php

/*
* 0 - current day
* 1 - prev day
*/

namespace Dejurin\ExchangeRates\Models;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Service\RatesData;

class Currency
{
    private $parameters;
    private $base_currency;
    private $currency;
    private $rates = null;
    private $reverse = false;
    private $zero_symbol = '&ndash;';
    private $settings = [];
    private $base_currency_source;

    public function __construct($parameters, $currency)
    {
        $settings = get_option(Settings::$option_name, []);
        $this->settings = wp_parse_args($settings, Settings::get_defaults());
        $this->parameters = $parameters;
        $this->reverse = isset($parameters['reverse']) ? $parameters['reverse'] : $this->reverse;
        $this->set_currencies($parameters['base_currency'], $currency);
    }

    public static function get_fmt($currency_format)
    {
        $list = CurrencyFormat::get_list();

        if (!is_array($currency_format)) {
            $currency_format = (int) $currency_format;

            if (isset($list[$currency_format])) {
                return $list[$currency_format];
            }
        }

        return $list[3];
    }

    public static function for_format($value, $currency_format, $decimals)
    {
        $fmt = self::get_fmt($currency_format);
        $decimals = max( 0, min( 7, (int) $decimals ) );

        if ($value === null) {
            $value = 0;
        }

        if ( 0 === $decimals ) {
            return number_format( $value, 0, $fmt['decimal_point'], $fmt['thousands_sep'] );
        }

        return rtrim(
            rtrim(
                number_format(
                    $value,
                    $decimals,
                    $fmt['decimal_point'],
                    $fmt['thousands_sep']),
                '0'),
            '.,');
    }

    public function set_currencies($base_currency, $currency)
    {
        $this->base_currency = $base_currency;
        $this->currency = $currency;

        if (is_null($this->rates)) {
            $this->rates = RatesData::get();
        }

        $this->base_currency_source = RatesData::get_base($this->rates);
    }

    public function get_date()
    {
        return RatesData::get_date($this->rates, 0);
    }

    public function get_source_id()
    {
        return RatesData::get_source($this->rates, $this->settings['source_id']);
    }

    public function has_rate($index = 0)
    {
        return RatesData::has_rate($this->rates, $this->currency, $index)
            && RatesData::has_rate($this->rates, $this->base_currency, $index);
    }

    public function has_previous_rate()
    {
        return $this->has_rate(1);
    }

    public function get_rate($index = 0)
    {
        if ($this->has_rate($index)) {
            $currency_rate = RatesData::get_rate($this->rates, $this->currency, $index);
            $base_rate = RatesData::get_rate($this->rates, $this->base_currency, $index);

            if ($this->reverse) {
                $currency_rate = 1 / $currency_rate;
                $base_rate = 1 / $base_rate;
            }

            if ($this->base_currency_source === $this->base_currency) {
                return (!$this->parameters['inverse']) ? $base_rate / $currency_rate : $currency_rate;
            } else {
                return (!$this->parameters['inverse']) ? (1 / $currency_rate) * $base_rate : $currency_rate * 1 / $base_rate;
            }
        } else {
            return 0;
        }
    }

    public function get_rate_format($index = 0, $amount = true, $decimals = false)
    {
        $result = self::for_format(
            $this->get_rate($index) * ($amount ? $this->parameters['amount'] : 1),
            $this->settings['currency_format'],
            ($decimals) ? $this->parameters['decimals'] : $this->settings['decimals']
        );

        return strcmp($result, '0') ? $result : $this->zero_symbol;
    }

    public function get_amount()
    {
        return self::for_format(
            $this->parameters['amount'],
            $this->settings['currency_format'],
            $this->settings['decimals']
        );
    }

    public function get_change()
    {
        $rate_0 = $this->get_rate(0);
        $rate_1 = $this->get_rate(1);

        if ($this->currency !== $this->base_currency &&
            $this->has_previous_rate() &&
            $rate_0 > 0 && $rate_1 > 0 &&
            $rate_0 !== $rate_1) {
            return ($rate_0 - $rate_1) * $this->parameters['amount'];
        }
    }

    public function get_change_format($raw = false)
    {
        if ($this->currency !== $this->base_currency) {
            $result = self::for_format(
                $this->get_change(),
                $this->settings['currency_format'],
                $this->settings['decimals']
            );

            return strcmp($result, '0') ? $result : (($raw) ? null : $this->zero_symbol);
        }
    }

    public function get_change_percentage()
    {
        $rate_0 = $this->get_rate(0);
        $rate_1 = $this->get_rate(1);

        if ($this->currency !== $this->base_currency &&
            $this->has_previous_rate() &&
            0 !== $rate_0 && 0 !== $rate_1) {
            $pre = '';
            $decimal = 0;
            $value = ( ( $rate_0 - $rate_1 ) / $rate_1 ) * 100;

            $value_abs = abs($value);

            if ($value_abs > 99) {
                $decimal = 0;
            } elseif ($value_abs > 9) {
                $decimal = 1;
            } elseif ($value_abs > 0) {
                $decimal = 2;
            } else {
                return '&ndash;';
            }
            $pre = (1 === $this->get_trend()) ? '+' : '';
            $result = self::for_format($value, $this->settings['currency_format'], $decimal);

            return strcmp($result, '0') ? $pre.$result.'&#x25;' : $this->zero_symbol;
        } else {
            return '&ndash;';
        }
    }

    public function get_trend()
    {
        if ($this->has_previous_rate() && !is_null($this->get_change_format($raw = true))) {
            $rate_0 = $this->get_rate(0);
            $rate_1 = $this->get_rate(1);

            if ($rate_0 > $rate_1 && 0 !== strcmp($rate_0, $rate_1)) {
                return 1;
            } elseif ($rate_0 === $rate_1 || 0 === strcmp($rate_0, $rate_1)) {
                return 0;
            } else {
                return -1;
            }
        }

        return false;
    }

    public function is_available()
    {
        return $this->has_rate(0);
    }
}
