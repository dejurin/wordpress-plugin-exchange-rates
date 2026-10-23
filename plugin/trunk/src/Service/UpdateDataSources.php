<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Request\Request;

class UpdateDataSources
{
    public static $rates_option_name = Plugin::PLUGIN_SLUG.'_rates';
    public static $req_rates = null;
    private static $rates_api_uri = 'https://api-bank.fex.to/';

    public static function update($force_source_id = null)
    {
        // Keep a database snapshot: get_option() may still contain another request's old settings.
        $settings_snapshot = self::option_snapshot( Settings::$option_name );
        self::clear_option_cache( Settings::$option_name );
        $settings = null === $settings_snapshot ? array() : maybe_unserialize( $settings_snapshot );
        $settings = wp_parse_args($settings, Settings::get_defaults());

        $source_id = $settings['source_id'];

        if (!is_null($force_source_id)) {
            $source_id = $force_source_id;
        }

        if (!isset(Sources::get_list()[$source_id])) {
            self::save_state( array( 'rates_available' => false ), $settings_snapshot );

            self::$req_rates = [
                'status' => false,
                'data' => null,
                'error' => 'Unknown exchange-rate source.',
            ];

            return false;
        }

        $req = new Request(self::$rates_api_uri.'rates/'.$source_id.'.json');
        self::$req_rates = $req->data();

        if (self::$req_rates['status']) {
            $payload_error = RatesData::validate_payload(self::$req_rates['data'], $source_id);
            if ('' !== $payload_error) {
                self::$req_rates['status'] = false;
                self::$req_rates['error'] = $payload_error;
            }
        }

        $state = array();
        if (self::$req_rates['status']) {
            if ( ! self::guarded_update_option( self::$rates_option_name, self::$req_rates['data'], $settings_snapshot ) ) {
                self::$req_rates['status'] = false;
                self::$req_rates['error'] = 'Unable to save exchange-rate data.';
            } else {
                $state['base_currency'] = self::$req_rates['data']['base'];
                $state['update_timestamp'] = time();
            }
        }

        $state['rates_available'] = self::$req_rates['status'];
        if ( ! self::save_state( $state, $settings_snapshot ) ) {
            self::$req_rates['status'] = false;
            self::$req_rates['error'] = $settings_snapshot !== self::option_snapshot( Settings::$option_name )
                ? 'Exchange-rate settings changed during the update.'
                : 'Unable to save exchange-rate settings.';
        }

        return self::$req_rates['status'];
    }

    /**
     * Persist only service-owned fields without opening them to settings-form input.
     *
     * @param array $state Availability and, after a validated success, base and UTC time.
     * @param string|null $settings_snapshot Serialized settings before the provider request.
     * @return bool Whether the requested state is stored, including an unchanged value.
     */
    private static function save_state( array $state, $settings_snapshot )
    {
        $settings = wp_parse_args( maybe_unserialize( $settings_snapshot ), Settings::get_defaults() );
        // The form sanitizer remains active. This scoped filter adds only our internal patch.
        $apply_state = static function ( $value ) use ( $state ) {
            return is_array( $value ) ? array_merge( $value, $state ) : $value;
        };
        $filter = 'sanitize_option_' . Settings::$option_name;
        add_filter( $filter, $apply_state, 20 );
        try {
            if ( ! self::guarded_update_option( Settings::$option_name, array_merge( $settings, $state ), $settings_snapshot ) ) {
                return false;
            }
        } finally {
            remove_filter( $filter, $apply_state, 20 );
        }

        // Verify persisted state without a possibly stale option cache.
        $saved = maybe_unserialize( self::option_snapshot( Settings::$option_name ) );
        foreach ( $state as $key => $value ) {
            if ( ! is_array( $saved ) || ! array_key_exists( $key, $saved ) || $saved[ $key ] !== $value ) {
                return false;
            }
        }
        return true;
    }

	/** Read the persisted value without a request-local or persistent option cache. */
	private static function option_snapshot( $option ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option ) );
	}

	private static function clear_option_cache( $option ) {
		wp_cache_delete( $option, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Save only while the selected settings still match the request's snapshot.
	 *
	 * A PHP check followed by update_option() has a check/write race. The SQL
	 * predicate guards both rates and metadata, including a concurrent first
	 * insert. Keep option sanitizers, veto filters and notifications; never use
	 * add_option()'s upsert to replace another request's newly inserted value.
	 *
	 * @param string      $option Option owned by this service.
	 * @param array       $value Exact validated value to persist.
	 * @param string|null $settings_snapshot Serialized settings before HTTP.
	 * @return bool Whether this request saved the exact value, or it was unchanged.
	 */
	private static function guarded_update_option( $option, array $value, $settings_snapshot ) {
		global $wpdb;
		$previous = self::option_snapshot( $option );
		self::clear_option_cache( $option );
		$old_value = null === $previous ? false : maybe_unserialize( $previous );
		$filtered = sanitize_option( $option, $value );
		$filtered = apply_filters( 'pre_update_option_' . $option, $filtered, $old_value, $option );
		$filtered = apply_filters( 'pre_update_option', $filtered, $option, $old_value );
		if ( $filtered !== $value ) {
			return false;
		}
		$serialized = maybe_serialize( $value );
		if ( $previous === $serialized ) {
			return $settings_snapshot === self::option_snapshot( Settings::$option_name )
				&& $serialized === self::option_snapshot( $option );
		}
		$guard = null === $settings_snapshot ? 'selected.option_value IS NULL' : 'BINARY selected.option_value = BINARY %s';
		if ( null === $previous ) {
			do_action( 'add_option', $option, $value );
			$sql = "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload)
				SELECT %s, %s, 'no' FROM (SELECT 1) AS seed
				LEFT JOIN {$wpdb->options} AS selected ON selected.option_name = %s
				WHERE {$guard}";
			$parameters = array( $option, $serialized, Settings::$option_name );
		} else {
			do_action( 'update_option', $option, $old_value, $value );
			$sql = "UPDATE {$wpdb->options} AS target
				LEFT JOIN {$wpdb->options} AS selected ON selected.option_name = %s
				SET target.option_value = %s
				WHERE target.option_name = %s AND BINARY target.option_value = BINARY %s AND {$guard}";
			$parameters = array( Settings::$option_name, $serialized, $option, $previous );
		}
		if ( null !== $settings_snapshot ) {
			$parameters[] = $settings_snapshot;
		}
		$written = $wpdb->query( $wpdb->prepare( $sql, $parameters ) );
		if ( 1 !== $written ) {
			return false;
		}
		self::clear_option_cache( $option );
		if ( null === $previous ) {
			do_action( 'add_option_' . $option, $option, $value );
			do_action( 'added_option', $option, $value );
		} else {
			do_action( 'update_option_' . $option, $old_value, $value, $option );
			do_action( 'updated_option', $option, $old_value, $value );
		}
		// Verify storage itself, not an option cache populated by an update hook.
		return $serialized === self::option_snapshot( $option );
	}
}
