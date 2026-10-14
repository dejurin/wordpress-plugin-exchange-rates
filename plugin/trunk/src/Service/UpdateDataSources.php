<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Models\DataSources;
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
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());

        // Update sources
        $get_data_sources = DataSources::getInstance();
        // $sources = $get_data_sources->get_sources_data();
        $source_id = $settings['source_id'];

        if (!is_null($force_source_id)) {
            $source_id = $force_source_id;
        }

        if (!isset(Sources::get_list()[$source_id])) {
            self::save_state( array( 'rates_available' => false ) );

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
            $payload_error = RatesData::validate_payload(self::$req_rates['data']);
            if ('' !== $payload_error) {
                self::$req_rates['status'] = false;
                self::$req_rates['error'] = $payload_error;
            }
        }

        $state = array();
        if (self::$req_rates['status']) {
            update_option(self::$rates_option_name, self::$req_rates['data']);
            if ( get_option( self::$rates_option_name ) !== self::$req_rates['data'] ) {
                self::$req_rates['status'] = false;
                self::$req_rates['error'] = 'Unable to save exchange-rate data.';
            } else {
                $state['base_currency'] = self::$req_rates['data']['base'];
                $state['update_timestamp'] = time();
            }
        }

        $state['rates_available'] = self::$req_rates['status'];
        if ( ! self::save_state( $state ) ) {
            self::$req_rates['status'] = false;
            self::$req_rates['error'] = 'Unable to save exchange-rate settings.';
        }

        return self::$req_rates['status'];
    }

    /**
     * Persist only service-owned fields without opening them to settings-form input.
     *
     * @param array $state Availability and, after a validated success, base and UTC time.
     * @return bool Whether the requested state is stored, including an unchanged value.
     */
    private static function save_state( array $state )
    {
        $settings = wp_parse_args( get_option( Settings::$option_name, array() ), Settings::get_defaults() );
        // The form sanitizer remains active. This scoped filter adds only our internal patch.
        $apply_state = static function ( $value ) use ( $state ) {
            return is_array( $value ) ? array_merge( $value, $state ) : $value;
        };
        $filter = 'sanitize_option_' . Settings::$option_name;
        add_filter( $filter, $apply_state, 20 );
        try {
            update_option( Settings::$option_name, array_merge( $settings, $state ) );
        } finally {
            remove_filter( $filter, $apply_state, 20 );
        }

        // update_option() also returns false for an unchanged value; verify the actual state.
        $saved = get_option( Settings::$option_name, array() );
        foreach ( $state as $key => $value ) {
            if ( ! is_array( $saved ) || ! array_key_exists( $key, $saved ) || $saved[ $key ] !== $value ) {
                return false;
            }
        }
        return true;
    }
}
