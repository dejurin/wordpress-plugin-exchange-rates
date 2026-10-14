<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Plugin;

class RatesData
{
    public static function get($rates = null)
    {
        if (null === $rates) {
            $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        }

        return is_array($rates) ? $rates : [];
    }

    public static function get_source($rates = null, $fallback = 'currencyrate')
    {
        $rates = self::get($rates);

        return isset($rates['source']) && is_string($rates['source']) && '' !== $rates['source'] ? $rates['source'] : $fallback;
    }

    public static function get_base($rates = null, $fallback = 'USD')
    {
        $rates = self::get($rates);
        $base = isset($rates['base']) ? strtoupper((string) $rates['base']) : '';

        return preg_match('/^[A-Z]{2,6}$/', $base) ? $base : $fallback;
    }

    public static function get_rates($rates = null, $index = 0)
    {
        $rates = self::get($rates);

        if (!isset($rates['data'][$index]['rates']) || !is_array($rates['data'][$index]['rates'])) {
            return [];
        }

        return $rates['data'][$index]['rates'];
    }

    public static function get_current_rates($rates = null)
    {
        return self::get_rates($rates, 0);
    }

    public static function get_previous_rates($rates = null)
    {
        return self::get_rates($rates, 1);
    }

    public static function has_current_rates($rates = null)
    {
        return !empty(self::get_current_rates($rates));
    }

    public static function has_previous_rates($rates = null)
    {
        return !empty(self::get_previous_rates($rates));
    }

    public static function get_rate_codes($rates = null)
    {
        return array_keys(self::get_current_rates($rates));
    }

    public static function get_rate($rates, $currency, $index = 0)
    {
        $currency = strtoupper((string) $currency);
        $data = self::get_rates($rates, $index);

        if (!isset($data[$currency]) || !is_numeric($data[$currency]) || (float) $data[$currency] <= 0) {
            return null;
        }

        return (float) $data[$currency];
    }

    public static function has_rate($rates, $currency, $index = 0)
    {
        return null !== self::get_rate($rates, $currency, $index);
    }

    public static function get_date($rates = null, $index = 0)
    {
        $rates = self::get($rates);
        $fallback = gmdate('Y-m-d H:i:s');

        return [
            'local_time' => isset($rates['data'][$index]['local_time']) && is_string($rates['data'][$index]['local_time']) ? $rates['data'][$index]['local_time'] : $fallback,
            'put_time' => isset($rates['data'][$index]['put_time']) && is_string($rates['data'][$index]['put_time']) ? $rates['data'][$index]['put_time'] : $fallback,
        ];
    }

    public static function validate_payload($payload)
    {
        if (!is_array($payload)) {
            return 'Rates response is not a JSON object.';
        }

        if (!isset($payload['source']) || !is_string($payload['source']) || '' === $payload['source']) {
            return 'Rates response is missing source.';
        }

        $base = isset($payload['base']) ? strtoupper((string) $payload['base']) : '';
        if (!preg_match('/^[A-Z]{2,6}$/', $base)) {
            return 'Rates response is missing base currency.';
        }

        $current_rates = self::get_current_rates($payload);
        if (empty($current_rates)) {
            return 'Rates response is missing current rates.';
        }

        foreach ($current_rates as $rate) {
            if (is_numeric($rate) && (float) $rate > 0) {
                return '';
            }
        }

        return 'Rates response does not contain usable numeric rates.';
    }
}