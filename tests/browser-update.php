<?php
/** Save and restore the disposable browser fixture around real HTTP update requests. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Browser update fixture requires the disposable local environment.' );
}
$key = 'exchange_rates_browser_update_snapshot';
$snapshot = get_option( $key );
$mode = isset( $args[0] ) ? $args[0] : '';
if ( 'seed' === $mode ) {
	if ( $snapshot ) {
		WP_CLI::error( 'Restore the previous browser update fixture first.' );
	}
	$snapshot = array( 'settings' => get_option( 'exchange-rates' ), 'rates' => get_option( 'exchange-rates_rates' ), 'started' => time() );
	update_option( $key, $snapshot );
	$settings = $snapshot['settings'];
	$settings['update_timestamp'] = time() - 61;
	$settings['rates_available'] = false;
	update_option( 'exchange-rates', $settings );
	$rates = $snapshot['rates'];
	$rates['data'][0]['rates']['EUR'] = 2;
	update_option( 'exchange-rates_rates', $rates );
	echo wp_json_encode( array( 'seeded' => true ) );
} elseif ( 'restore' === $mode && $snapshot ) {
	update_option( 'exchange-rates', $snapshot['settings'] );
	update_option( 'exchange-rates_rates', $snapshot['rates'] );
	if ( $snapshot['settings'] !== get_option( 'exchange-rates' ) || $snapshot['rates'] !== get_option( 'exchange-rates_rates' ) ) {
		WP_CLI::error( 'Could not restore browser update data.' );
	}
	delete_option( $key );
	echo wp_json_encode( array( 'restored' => true ) );
} elseif ( 'verify' === $mode && $snapshot ) {
	$settings = get_option( 'exchange-rates' );
	$rates = get_option( 'exchange-rates_rates' );
	$keys = array_flip( array( 'source_id', 'currency_format', 'decimals' ) );
	$valid = true === $settings['rates_available'] && 'USD' === $settings['base_currency']
		&& $settings['update_timestamp'] >= $snapshot['started']
		&& 1.25 === $rates['data'][0]['rates']['EUR']
		&& array_intersect_key( $settings, $keys ) === array_intersect_key( $snapshot['settings'], $keys );
	echo wp_json_encode( array( 'valid' => $valid, 'timestamp' => $settings['update_timestamp'] ) );
} else {
	WP_CLI::error( 'Expected seed, verify or restore with an existing snapshot.' );
}
