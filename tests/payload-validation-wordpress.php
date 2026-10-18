<?php
/** Actual HTTP/update/persistence contracts; disposable local WordPress only. */
use Dejurin\ExchangeRates\Service\UpdateDataSources;
use Dejurin\ExchangeRates\Cron\UpdateRates;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Payload tests require the isolated local site.' );
}
require __DIR__ . '/payload-validation-fixtures.php';
if ( ! function_exists( 'er_source_payload' ) ) { require __DIR__ . '/source-identity-fixture.php'; }
function er_payload_state() {
	wp_cache_delete( 'alloptions', 'options' );
	foreach ( array( 'exchange-rates', 'exchange-rates_rates' ) as $key ) { wp_cache_delete( $key, 'options' ); }
	return array( 'settings' => get_option( 'exchange-rates' ), 'rates' => get_option( 'exchange-rates_rates' ) );
}
if ( isset( $args[0] ) && 'request' === $args[0] ) {
	$case = json_decode( base64_decode( $args[1] ), true );
	$requests = 0;
	$filter = function ( $response, $http_args, $url ) use ( $case, &$requests ) {
		if ( 0 !== strpos( $url, 'https://api-bank.fex.to/rates/' ) ) { return $response; }
		++$requests;
		return array( 'headers' => array(), 'body' => wp_json_encode( $case['payload'] ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
	};
	add_filter( 'pre_http_request', $filter, 50, 3 );
	$success = null;
	if ( 'direct' === $case['mode'] ) { $success = UpdateDataSources::update( $case['source'] ); }
	if ( 'cron' === $case['mode'] ) { $success = UpdateRates::run_hourly( $case['source'] ); }
	$table = new \Dejurin\ExchangeRates\Shortcodes\CurrencyTable(); $table->init();
	echo wp_json_encode( array( 'success' => $success, 'requests' => $requests, 'state' => er_payload_state(), 'html' => ( 'render' === $case['mode'] || false === $success ) ? er_source_render() : array(), 'retry' => wp_next_scheduled( UpdateRates::$retry_action_name ), 'lock' => get_option( 'exchange-rates_refresh_lock' ) ) );
	return;
}
function er_payload_request( $payload, $source, $mode = 'direct' ) {
	$case = array( 'payload' => $payload, 'source' => $source, 'mode' => $mode );
	$r = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __FILE__ ) . ' request ' . escapeshellarg( base64_encode( wp_json_encode( $case ) ) ), array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$data = json_decode( $r->stdout, true );
	if ( 0 !== $r->return_code || ! is_array( $data ) || '' !== trim( $r->stderr ) ) { throw new RuntimeException( 'Invalid child result: ' . $r->stdout . $r->stderr ); }
	return $data;
}
global $checks, $failures;
$checks = 0; $failures = 0;
function er_payload_check( $pass, $label ) {
	global $checks, $failures; ++$checks;
	if ( ! $pass ) { ++$failures; WP_CLI::log( 'FAIL: ' . $label ); }
}
$original = er_payload_state(); $cron = get_option( 'cron' ); $lock = get_option( 'exchange-rates_refresh_lock' ); $backoff = get_transient( 'exchange-rates_refresh_after' );
try {
	$settings = er_source_settings( 'currencyrate' ); $settings['update_timestamp'] = time() - 7200;
	$old = er_validation_payload();
	update_option( 'exchange-rates', $settings ); update_option( 'exchange-rates_rates', $old );
	$reference = er_payload_request( $old, 'currencyrate', 'render' );
	foreach ( er_validation_invalid() as $name => $case ) {
		er_payload_state(); update_option( 'exchange-rates', $settings ); update_option( 'exchange-rates_rates', $old );
		$r = er_payload_request( $case[0], $case[1] );
		$expected = array( 'settings' => array_merge( $settings, array( 'rates_available' => false ) ), 'rates' => $old );
		er_payload_check( false === $r['success'] && 1 === $r['requests'], $name . ': reject HTTP200 malformed data' );
		er_payload_check( $expected === $r['state'], $name . ': preserve complete old payload/base/date/time/settings' );
		// Compare markup only after retention succeeds; pre-fix poison must not abort this suite.
		if ( $expected === $r['state'] ) { er_payload_check( $reference['html'] === $r['html'], $name . ': all seven displays remain identical' ); }
	}
	er_payload_state(); update_option( 'exchange-rates', $settings ); update_option( 'exchange-rates_rates', $old );
	$bad = $old; $bad['data'][0]['put_time'] = 'tomorrow';
	delete_transient( 'exchange-rates_refresh_after' ); delete_option( 'exchange-rates_refresh_lock' );
	$r = er_payload_request( $bad, 'currencyrate', 'cron' );
	er_payload_check( false === $r['success'] && $old === $r['state']['rates'] && $settings['update_timestamp'] === $r['state']['settings']['update_timestamp'], 'Cron rejects bad data and retains successful state' );
	er_payload_check( $r['retry'] > time() && ! $r['lock'], 'Cron schedules retry and releases lease after validation failure' );
	$reload = er_payload_request( $old, 'currencyrate', 'render' );
	er_payload_check( 0 === $reload['requests'] && $reference['html'] === $reload['html'], 'Next request renders old data without provider HTTP' );
	foreach ( er_validation_valid() as $name => $payload ) {
		$r = er_payload_request( $payload, $payload['source'] );
		er_payload_check( true === $r['success'] && $payload === $r['state']['rates'], $name . ': recovery stores exact supported payload' );
		er_payload_check( true === $r['state']['settings']['rates_available'] && $r['state']['settings']['update_timestamp'] >= time() - 30, $name . ': recovery advances success state' );
	}
} finally {
	er_payload_state();
	update_option( 'exchange-rates', $original['settings'] ); update_option( 'exchange-rates_rates', $original['rates'] ); update_option( 'cron', $cron );
	if ( false === $lock ) { delete_option( 'exchange-rates_refresh_lock' ); } else { update_option( 'exchange-rates_refresh_lock', $lock, false ); }
	if ( false === $backoff ) { delete_transient( 'exchange-rates_refresh_after' ); } else { set_transient( 'exchange-rates_refresh_after', $backoff, 300 ); }
}
er_payload_check( $original === er_payload_state(), 'Original fixture state restored' );
WP_CLI::log( sprintf( 'WordPress payload validation: %d/%d passed, %d failed.', $checks - $failures, $checks, $failures ) );
if ( $failures ) { WP_CLI::error( 'Payload validation failed.' ); }
