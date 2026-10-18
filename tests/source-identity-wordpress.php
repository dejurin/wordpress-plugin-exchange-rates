<?php
/** Persisted failed-source-switch regressions. Run in the disposable local site. */
use Dejurin\ExchangeRates\Admin\Admin;
use Dejurin\ExchangeRates\Admin\Settings\General\Sections\Settings as SettingsForm;
use Dejurin\ExchangeRates\Service\UpdateDataSources;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Source identity tests require the disposable local environment.' );
}
if ( ! function_exists( 'er_source_payload' ) ) { require_once __DIR__ . '/source-identity-fixture.php'; }
set_error_handler( function ( $level, $message, $file, $line ) {
	if ( error_reporting() & $level ) { throw new ErrorException( $message, 0, $level, $file, $line ); }
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );
function er_identity_state() {
	return array( 'settings' => get_option( 'exchange-rates' ), 'rates' => get_option( 'exchange-rates_rates' ), 'table' => get_option( 'widget_exchange-rates_currency-table' ), 'converter' => get_option( 'widget_exchange-rates_currency-converter' ), 'sidebars' => get_option( 'sidebars_widgets' ) );
}
if ( isset( $args[0] ) && 'request' === $args[0] ) {
	$case = json_decode( base64_decode( $args[1] ), true );
	$requests = array();
	add_filter( 'pre_http_request', function ( $response, $http_args, $url ) use ( &$requests, $case ) {
		if ( false === strpos( $url, 'https://api-bank.fex.to/rates/' ) ) { return $response; }
		$requests[] = $url;
		if ( 'failure' === $case['provider'] ) { return new WP_Error( 'synthetic_failure', 'Local provider failure.' ); }
		$source = false !== strpos( $url, '/eu-ecb.json' ) ? 'eu-ecb' : 'currencyrate';
		return array( 'headers' => array(), 'body' => wp_json_encode( er_source_payload( $source ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
	}, 40, 3 );
	if ( 'seed' === $case['operation'] ) {
		update_option( 'exchange-rates', er_source_settings( $case['source'] ) );
		update_option( 'exchange-rates_rates', er_source_payload( $case['source'] ) );
	} elseif ( 'switch' === $case['operation'] ) {
		Admin::run();
		SettingsForm::register_settings();
		update_option( 'exchange-rates', array( 'source_id' => $case['source'], 'currency_format' => 3, 'decimals' => 2 ) );
	} elseif ( 'refresh' === $case['operation'] ) {
		UpdateDataSources::update();
	} elseif ( 'legacy' === $case['operation'] ) {
		$rates = get_option( 'exchange-rates_rates' );
		unset( $rates['source'] );
		update_option( 'exchange-rates_rates', $rates );
	}
	// CurrencyTable is registered on init, already fired before this eval-file.
	$table_shortcode = new \Dejurin\ExchangeRates\Shortcodes\CurrencyTable();
	$table_shortcode->init();
	echo wp_json_encode( array( 'state' => er_identity_state(), 'html' => er_source_render(), 'requests' => $requests ) );
	return;
}
function er_identity_request( $operation, $source, $provider = 'failure' ) {
	$case = array( 'operation' => $operation, 'source' => $source, 'provider' => $provider );
	$result = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __FILE__ ) . ' request ' . escapeshellarg( base64_encode( wp_json_encode( $case ) ) ), array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$data = json_decode( $result->stdout, true );
	if ( 0 !== $result->return_code || '' !== trim( $result->stderr ) || ! is_array( $data ) ) { throw new RuntimeException( 'Invalid source child: ' . $result->stdout . $result->stderr ); }
	return $data;
}
global $checks, $failures;
$checks = 0; $failures = 0;
function er_identity_check( $condition, $label ) {
	global $checks, $failures;
	++$checks;
	if ( ! $condition ) { ++$failures; WP_CLI::log( 'FAIL: ' . $label ); }
}
$original = er_identity_state();
try {
	foreach ( array( 'currencyrate' => 'eu-ecb', 'eu-ecb' => 'currencyrate' ) as $old => $next ) {
		$before = er_identity_request( 'seed', $old );
		foreach ( $before['html'] as $name => $html ) { er_identity_check( false !== strpos( $html, 'cr-exchange-rates' ) && false === strpos( $html, 'Error:' ) && false === strpos( $html, '[exchange-rates_' ), $old . '/' . $name . ': actual renderer' ); }
		$failed = er_identity_request( 'switch', $next );
		$expected = $before['state'];
		$expected['settings']['source_id'] = $next;
		$expected['settings']['rates_available'] = false;
		er_identity_check( $expected === $failed['state'], $old . ': failed switch preserves payload/base/time/preferences/widget options' );
		er_identity_check( array( 'https://api-bank.fex.to/rates/' . $next . '.json' ) === $failed['requests'], $old . ': real settings hook requests selected source' );
		foreach ( array( 'failed' => $failed, 'reload' => er_identity_request( 'render', $next ), 'repeat-failure' => er_identity_request( 'refresh', $next ) ) as $phase => $result ) {
			er_identity_check( $expected === $result['state'], $old . '/' . $phase . ': stored state preserved' );
			foreach ( $before['html'] as $name => $html ) { er_identity_check( $html === $result['html'][ $name ], $old . '/' . $phase . '/' . $name . ': preserve values, direction, dates and source captions' ); }
		}
		$success = er_identity_request( 'refresh', $next, 'success' );
		er_identity_check( $next === $success['state']['rates']['source'] && $next === $success['state']['settings']['source_id'] && true === $success['state']['settings']['rates_available'], $old . ': recovery adopts selected source' );
		$reference = er_identity_request( 'seed', $next );
		er_identity_check( $reference['html'] === $success['html'], $old . ': recovery matches direct successful source render' );
		$legacy = er_identity_request( 'legacy', $next );
		er_identity_check( $reference['html'] === $legacy['html'], $old . ': missing legacy source keeps existing selected-source fallback' );
	}
} finally {
	update_option( 'exchange-rates', $original['settings'] );
	update_option( 'exchange-rates_rates', $original['rates'] );
}
er_identity_check( $original === er_identity_state(), 'Original settings/rates/widgets/sidebar state restored' );
WP_CLI::log( sprintf( 'Source identity: %d/%d passed, %d failed.', $checks - $failures, $checks, $failures ) );
if ( $failures ) { WP_CLI::error( 'Source identity regressions failed.' ); }
