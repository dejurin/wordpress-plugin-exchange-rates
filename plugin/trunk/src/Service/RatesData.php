<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Models\Sources;

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

        if (!isset($data[$currency]) || !self::is_valid_rate($data[$currency])) {
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

	/** Accept only positive finite values whose reciprocal remains usable. */
	private static function is_valid_rate( $value ) {
		if ( ! is_numeric( $value ) ) {
			return false;
		}
		$rate = (float) $value;
		return is_finite( $rate ) && $rate > 0 && is_finite( 1 / $rate );
	}

	/** Require an absolute calendar date, without relative or normalized-invalid dates. */
	private static function is_valid_date( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) || false !== strpos( $value, "\0" ) ) {
			return false;
		}
		$date = date_parse( $value );
		if ( $date['error_count'] || $date['warning_count'] || false === $date['year'] || false === $date['month'] || false === $date['day']
			|| ! checkdate( $date['month'], $date['day'], $date['year'] ) ) {
			return false;
		}
		// PHP 5.6 reports numeric zone offsets in minutes; PHP 7.2+ uses seconds.
		if ( isset( $date['zone'] ) && abs( $date['zone'] ) >= ( PHP_VERSION_ID < 70200 ? 1440 : 86400 ) ) {
			return false;
		}
		if ( isset( $date['relative'] ) ) {
			// PHP also marks RFC weekdays as relative. Accept only a complete RFC date.
			$rfc = \DateTime::createFromFormat( DATE_RFC2822, $value, new \DateTimeZone( 'UTC' ) );
			$calendar = $date['year'] . '-' . $date['month'] . '-' . $date['day'];
			if ( ! $rfc || $calendar !== $rfc->format( 'Y-n-j' ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate a new response before persistence; do not migrate existing saved payloads.
	 *
	 * @param mixed       $payload         Decoded API response.
	 * @param string|null $expected_source Requested source, when known by the caller.
	 * @return string Empty on success, otherwise an internal diagnostic.
	 */
	public static function validate_payload( $payload, $expected_source = null ) {
		if ( ! is_array( $payload ) ) {
			return 'Rates response is not a JSON object.';
		}
		if ( ! isset( $payload['source'] ) || ! is_string( $payload['source'] ) || ! isset( Sources::get_list()[ $payload['source'] ] ) ) {
			return 'Rates response has an unknown source.';
		}
		if ( null !== $expected_source && $expected_source !== $payload['source'] ) {
			return 'Rates response does not match the requested source.';
		}
		$base = isset( $payload['base'] ) && is_string( $payload['base'] ) ? strtoupper( $payload['base'] ) : '';
		if ( ! preg_match( '/^[A-Z]{2,6}$/', $base ) ) {
			return 'Rates response is missing base currency.';
		}
		if ( ! isset( $payload['data'] ) || ! is_array( $payload['data'] ) || ! isset( $payload['data'][0] ) ) {
			return 'Rates response is missing current rates.';
		}
		foreach ( array( 0, 1 ) as $index ) {
			// A previous close is optional; current-only providers remain supported.
			if ( 1 === $index && ( ! isset( $payload['data'][1] ) || array() === $payload['data'][1] ) ) {
				continue;
			}
			$record = $payload['data'][ $index ];
			if ( ! is_array( $record ) || ! isset( $record['rates'] ) || ! is_array( $record['rates'] ) || ! isset( $record['rates'][ $base ] ) ) {
				return 'Rates response is missing rates or its base rate.';
			}
			foreach ( array( 'local_time', 'put_time' ) as $key ) {
				if ( ! isset( $record[ $key ] ) || ! self::is_valid_date( $record[ $key ] ) ) {
					return 'Rates response has a missing or invalid date.';
				}
			}
			$minimum = INF;
			$maximum = 0;
			foreach ( $record['rates'] as $rate ) {
				if ( ! self::is_valid_rate( $rate ) ) {
					return 'Rates response contains an invalid numeric rate.';
				}
				$minimum = min( $minimum, (float) $rate );
				$maximum = max( $maximum, (float) $rate );
			}
			if ( ! is_finite( $maximum / $minimum ) ) {
				return 'Rates response exceeds the supported conversion range.';
			}
		}
		return '';
	}
}
