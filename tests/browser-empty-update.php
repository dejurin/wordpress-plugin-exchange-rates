<?php
/** Request-to-request first-load recovery fixture; never installed on ordinary sites. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'First-load tests require the disposable local environment.' );
}

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

$key = 'exchange_rates_empty_update_snapshot';
$provider_key = 'exchange_rates_empty_update_provider';
$snapshot = get_option( $key );
$mode = isset( $args[0] ) ? $args[0] : '';
$options = array( Settings::$option_name, UpdateDataSources::$rates_option_name,
	'widget_exchange-rates_currency-table', 'widget_exchange-rates_currency-converter',
	'cron', 'exchange-rates_refresh_lock', '_transient_exchange-rates_refresh_after',
	'_transient_timeout_exchange-rates_refresh_after' );
$preferences = function ( $settings ) {
	return array_diff_key( $settings, array_flip( array( 'rates_available', 'update_timestamp', 'base_currency' ) ) );
};

if ( 'seed' === $mode ) {
	if ( $snapshot || get_option( $provider_key ) ) {
		WP_CLI::error( 'Restore the previous first-load fixture before seeding.' );
	}
	$snapshot = array( 'options' => array(), 'started' => time() );
	foreach ( $options as $option ) {
		$snapshot['options'][ $option ] = get_option( $option );
	}
	update_option( $key, $snapshot, false );
	update_option( $provider_key, array( 'mode' => 'failure', 'calls' => 0 ), false );
	$settings = wp_parse_args( $snapshot['options'][ Settings::$option_name ], Settings::get_defaults() );
	$settings['source_id'] = 'currencyrate';
	$settings['update_timestamp'] = 0;
	$settings['rates_available'] = false;
	update_option( Settings::$option_name, $settings );
	delete_option( UpdateDataSources::$rates_option_name );
	$snapshot['preferences'] = $preferences( get_option( Settings::$option_name ) );
	update_option( $key, $snapshot, false );
	$result = UpdateDataSources::update();
	if ( false !== $result || false !== get_option( UpdateDataSources::$rates_option_name ) ) {
		WP_CLI::error( 'The initial provider failure was not reproduced.' );
	}
	echo wp_json_encode( array( 'seeded' => true ) );
} elseif ( 'restore' === $mode && $snapshot ) {
	delete_option( $provider_key );
	foreach ( $snapshot['options'] as $option => $value ) {
		if ( false === $value ) {
			delete_option( $option );
		} else {
			update_option( $option, $value );
		}
		if ( get_option( $option ) !== $value ) {
			WP_CLI::error( 'Could not restore option: ' . $option );
		}
	}
	delete_option( $key );
	echo wp_json_encode( array( 'restored' => true ) );
} elseif ( in_array( $mode, array( 'healthy', 'failure' ), true ) && $snapshot ) {
	$provider = get_option( $provider_key );
	$provider['mode'] = 'healthy' === $mode ? 'success' : 'failure';
	update_option( $provider_key, $provider );
	echo wp_json_encode( array( 'ready' => true ) );
} elseif ( 'expire' === $mode && $snapshot ) {
	$settings = get_option( Settings::$option_name );
	$settings['update_timestamp'] = time() - 61;
	update_option( Settings::$option_name, $settings );
	echo wp_json_encode( array( 'ready' => true ) );
} elseif ( 'state' === $mode && $snapshot ) {
	$settings = get_option( Settings::$option_name );
	$rates = get_option( UpdateDataSources::$rates_option_name );
	$provider = get_option( $provider_key );
	$widgets_unchanged = true;
	foreach ( array_slice( $options, 2, 2 ) as $option ) {
		$widgets_unchanged = $widgets_unchanged && get_option( $option ) === $snapshot['options'][ $option ];
	}
	echo wp_json_encode( array(
		'calls' => $provider['calls'], 'has_rates' => is_array( $rates ),
		'rates_hash' => hash( 'sha256', serialize( $rates ) ),
		'available' => $settings['rates_available'], 'timestamp' => $settings['update_timestamp'],
		'valid_rates' => is_array( $rates ) && 'currencyrate' === $rates['source'] && 'USD' === $rates['base'] && 1.25 === $rates['data'][0]['rates']['EUR'],
		'preferences_unchanged' => $preferences( $settings ) === $snapshot['preferences'],
		'widgets_unchanged' => $widgets_unchanged,
	) );
} else {
	WP_CLI::error( 'Expected seed, state, healthy, failure, expire or restore with a matching snapshot.' );
}
