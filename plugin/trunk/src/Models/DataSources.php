<?php

namespace Dejurin\ExchangeRates\Models;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Request\Request;

class DataSources
{
    public static $transient_option_name = Plugin::PLUGIN_SLUG.'_sources';
    public static $last_good_option_name = Plugin::PLUGIN_SLUG . '_sources_last_good';
    private static $instance;

    private $sources_url = 'https://api-bank.fex.to/sources.json';

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

	public function get_sources_data() {
		$cached = get_transient( self::$transient_option_name );
		if ( $this->validate_sources_data( $cached ) ) {
			// Preserve a valid 1.3.2 cache before WordPress expires its transient.
			update_option( self::$last_good_option_name, $cached, false );
			return $cached;
		}
		if ( is_array( $cached ) && isset( $cached['status'], $cached['data'], $cached['error'] )
			&& false === $cached['status'] && is_string( $cached['error'] ) && '' !== $cached['error']
			&& ( array() === $cached['data'] || $this->validate_rows( $cached['data'] ) ) ) {
			return $cached;
		}

		$req = new Request( $this->sources_url );
		$response = $req->data();
		if ( $this->validate_sources_data( $response ) ) {
			update_option( self::$last_good_option_name, $response, false );
			set_transient( self::$transient_option_name, $response, HOUR_IN_SECONDS );
			return $response;
		}

		$previous = get_option( self::$last_good_option_name );
		$response = array(
			'status' => false,
			'data'   => $this->validate_sources_data( $previous ) ? $previous['data'] : array(),
			'error'  => ! empty( $response['error'] ) ? $response['error'] : __( 'Invalid source directory response.', 'exchange-rates' ),
		);
		// Repeated settings-page reads must not retry a failed provider immediately.
		set_transient( self::$transient_option_name, $response, 5 * MINUTE_IN_SECONDS );
		return $response;
	}

	private function validate_sources_data( $response ) {
		return is_array( $response ) && isset( $response['status'], $response['data'] )
			&& true === $response['status'] && $this->validate_rows( $response['data'] );
	}

	/** Validate every field consumed by the source table without changing valid rows. */
	private function validate_rows( $rows ) {
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return false;
		}
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['source'], $row['country'], $row['base'], $row['lastupdate'] )
				|| ! is_string( $row['source'] ) || '' === $row['source']
				|| ! is_string( $row['country'] ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/D', $row['country'] )
				|| ! is_string( $row['base'] ) || '' === $row['base']
				|| ! $this->validate_timestamp( $row['lastupdate'] ) ) {
				return false;
			}
		}
		return true;
	}

	/** Accept positive integer Unix seconds, including the legacy numeric-string form. */
	private function validate_timestamp( $value ) {
		if ( is_int( $value ) ) {
			return $value > 0;
		}
		if ( ! is_string( $value ) || ! preg_match( '/^[0-9]+$/D', $value ) ) {
			return false;
		}
		$digits = ltrim( $value, '0' );
		$max = (string) PHP_INT_MAX;
		return '' !== $digits && ( strlen( $digits ) < strlen( $max )
			|| ( strlen( $digits ) === strlen( $max ) && strcmp( $digits, $max ) <= 0 ) );
	}
}
