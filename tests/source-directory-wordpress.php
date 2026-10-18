<?php
/** Source-directory regressions. Run only on a disposable local WordPress site. */
use Dejurin\ExchangeRates\Models\DataSources;
use Dejurin\ExchangeRates\Admin\Settings\General\Sections\SourceTable;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Source-directory tests require the isolated local site.' );
}
function er_directory_valid() {
	return array( array( 'source' => 'currencyrate', 'country' => 'xx', 'base' => 'USD', 'lastupdate' => 1767355200, 'category' => 'org', 'checksum' => 'preserve-extra-fields' ) );
}
function er_directory_response( $payload ) {
	return array( 'headers' => array(), 'body' => wp_json_encode( $payload ), 'response' => array( 'code' => 200 ), 'cookies' => array() );
}
$requests = 0;
$http_response = er_directory_response( er_directory_valid() );
$filter = function ( $response, $http_args, $url ) use ( &$requests, &$http_response ) {
	if ( 'https://api-bank.fex.to/sources.json' !== $url ) { return $response; }
	++$requests;
	return $http_response;
};
add_filter( 'pre_http_request', $filter, 99, 3 );
if ( isset( $args[0] ) && 'reload' === $args[0] ) {
	$http_response = new WP_Error( 'fixture', 'Synthetic directory outage.' );
	echo wp_json_encode( array( 'result' => DataSources::getInstance()->get_sources_data(), 'requests' => $requests ) );
	return;
}
global $checks, $failures;
$checks = 0; $failures = 0;
function er_directory_check( $pass, $label ) {
	global $checks, $failures;
	++$checks;
	if ( ! $pass ) { ++$failures; WP_CLI::log( 'FAIL: ' . $label ); }
}
function er_directory_render( $label ) {
	$level = ob_get_level();
	set_error_handler( function ( $severity, $message ) { throw new ErrorException( $message, 0, $severity ); }, E_WARNING | E_NOTICE );
	try {
		ob_start(); SourceTable::render(); $html = ob_get_clean();
		er_directory_check( true, $label . ': render without warnings/fatal errors' );
		return $html;
	} catch ( Exception $error ) {
		er_directory_check( false, $label . ': ' . $error->getMessage() );
	} catch ( Error $error ) {
		er_directory_check( false, $label . ': ' . $error->getMessage() );
	} finally {
		while ( ob_get_level() > $level ) { ob_end_clean(); }
		restore_error_handler();
	}
	return '';
}
$key = 'exchange-rates_sources';
$backup_key = 'exchange-rates_sources_last_good';
$original = array( 'cache' => get_transient( $key ), 'backup' => get_option( $backup_key ), 'rates' => get_option( 'exchange-rates_rates' ), 'settings' => get_option( 'exchange-rates' ) );
$good = array( 'status' => true, 'data' => er_directory_valid(), 'error' => null );
$invalid = array();
foreach ( array( 'invalid-date', '', null, true, false, array(), 1.5, 0, -1, '1.5', '1e9', '99999999999999999999999999999999' ) as $i => $value ) {
	$rows = er_directory_valid(); $rows[0]['lastupdate'] = $value; $invalid[ 'timestamp-' . $i ] = $rows;
}
foreach ( array( 'source', 'country', 'base', 'lastupdate' ) as $field ) {
	$rows = er_directory_valid(); unset( $rows[0][ $field ] ); $invalid[ 'missing-' . $field ] = $rows;
	$rows = er_directory_valid(); $rows[0][ $field ] = array( 'bad' ); $invalid[ 'array-' . $field ] = $rows;
}
foreach ( array( 'source' => '', 'country' => '../us', 'base' => '' ) as $field => $value ) {
	$rows = er_directory_valid(); $rows[0][ $field ] = $value; $invalid[ 'invalid-' . $field ] = $rows;
}
$invalid['empty-list'] = array(); $invalid['null-root'] = null; $invalid['scalar-root'] = 'bad';
$invalid['scalar-row'] = array( 'bad' ); $invalid['single-row-object'] = er_directory_valid()[0];
$rows = er_directory_valid(); $rows[] = array( 'source' => 'broken' ); $invalid['mixed-list'] = $rows;
try {
	delete_option( $backup_key ); set_transient( $key, $good, HOUR_IN_SECONDS );
	$loaded = DataSources::getInstance()->get_sources_data();
	er_directory_check( $good === $loaded && 0 === $requests, 'Accept unchanged 1.3.2 cache without HTTP' );
	er_directory_check( $good === get_option( $backup_key ), 'Retain legacy cache before its transient expires' );
	$reference = er_directory_render( 'Legacy cache' );
	global $wpdb;
	$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $backup_key ) );
	er_directory_check( in_array( $autoload, array( 'no', 'off', 'auto-off' ), true ), 'Last-good directory is not autoloaded' );
	foreach ( $invalid as $label => $payload ) {
		update_option( $backup_key, $good, false ); delete_transient( $key );
		$http_response = er_directory_response( $payload ); $before = $requests;
		$r = DataSources::getInstance()->get_sources_data();
		er_directory_check( false === $r['status'] && is_string( $r['error'] ) && '' !== $r['error'], $label . ': reject with diagnostic' );
		er_directory_check( $good['data'] === $r['data'] && $good === get_option( $backup_key ), $label . ': preserve complete last-good rows and timestamps' );
		$second = DataSources::getInstance()->get_sources_data();
		er_directory_check( $r === $second && $requests === $before + 1, $label . ': one request during failure backoff' );
		$html = er_directory_render( $label );
		er_directory_check( false !== strpos( $html, '2026-01-02 12:00:00' ) && false !== strpos( $html, 'ERROR' ), $label . ': old date remains visible with error notice' );
	}
	delete_option( $backup_key ); delete_transient( $key );
	$http_response = er_directory_response( $invalid['timestamp-0'] );
	$r = DataSources::getInstance()->get_sources_data();
	er_directory_check( false === $r['status'] && array() === $r['data'], 'Fresh installation returns a safe empty list on invalid data' );
	$html = er_directory_render( 'Fresh installation' );
	er_directory_check( false !== strpos( $html, 'ERROR' ) && false === strpos( $html, '1970-' ), 'No fabricated epoch date on a fresh installation' );
	set_transient( $key, array( 'status' => true, 'data' => $invalid['timestamp-0'], 'error' => null ), HOUR_IN_SECONDS );
	$http_response = er_directory_response( er_directory_valid() );
	$r = DataSources::getInstance()->get_sources_data();
	er_directory_check( $good === $r, 'Reject poisoned 1.3.2 cache and recover with valid HTTP data' );
	er_directory_check( $reference === er_directory_render( 'Recovery' ), 'Valid source table HTML is unchanged after recovery' );
	foreach ( array( 1, '1767355200', '01767355200' ) as $stamp ) {
		$rows = er_directory_valid(); $rows[0]['lastupdate'] = $stamp;
		$rows[] = array( 'source' => 'future-provider', 'country' => 'us', 'base' => 'LD', 'lastupdate' => 1767355200 );
		delete_transient( $key ); $http_response = er_directory_response( $rows );
		$r = DataSources::getInstance()->get_sources_data();
		er_directory_check( true === $r['status'] && $rows === $r['data'], 'Accept exact legacy integer/numeric-string timestamp and extra source/base values' );
		er_directory_render( 'Supported timestamp' );
	}
	update_option( $backup_key, $good, false ); delete_transient( $key );
	foreach ( array( new WP_Error( 'fixture', 'Synthetic directory outage.' ), array( 'response' => array( 'code' => 503 ), 'body' => '' ), array( 'response' => array( 'code' => 200 ), 'body' => '{broken' ) ) as $response ) {
		delete_transient( $key ); $http_response = $response;
		$r = DataSources::getInstance()->get_sources_data();
		er_directory_check( false === $r['status'] && $good['data'] === $r['data'] && $good === get_option( $backup_key ), 'Transport/HTTP/JSON failure preserves the last good directory' );
	}
	delete_transient( $key );
	$child = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __FILE__ ) . ' reload', array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$reload = json_decode( $child->stdout, true );
	er_directory_check( 0 === $child->return_code && '' === trim( $child->stderr ) && 1 === $reload['requests'] && $good['data'] === $reload['result']['data'], 'Retain old directory across PHP requests after transient expiry' );
	wp_cache_delete( $key, 'transient' ); wp_cache_delete( '_transient_' . $key, 'options' );
	$child = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __FILE__ ) . ' reload', array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$reload = json_decode( $child->stdout, true );
	er_directory_check( 0 === $child->return_code && 0 === $reload['requests'] && $good['data'] === $reload['result']['data'], 'Failure backoff persists across requests' );
	delete_transient( $key ); update_option( $backup_key, array( 'status' => true, 'data' => $invalid['timestamp-0'] ), false );
	$http_response = er_directory_response( $invalid['timestamp-0'] );
	$r = DataSources::getInstance()->get_sources_data();
	er_directory_check( false === $r['status'] && array() === $r['data'], 'Never render a corrupted backup' );
	er_directory_check( $original['rates'] === get_option( 'exchange-rates_rates' ) && $original['settings'] === get_option( 'exchange-rates' ), 'Rate payload, source selection and all settings remain unchanged' );
} finally {
	remove_filter( 'pre_http_request', $filter, 99 );
	if ( false === $original['cache'] ) { delete_transient( $key ); } else { set_transient( $key, $original['cache'], HOUR_IN_SECONDS ); }
	if ( false === $original['backup'] ) { delete_option( $backup_key ); } else { update_option( $backup_key, $original['backup'], false ); }
}
WP_CLI::log( sprintf( 'Source directory: %d/%d passed, %d failed.', $checks - $failures, $checks, $failures ) );
if ( $failures ) { WP_CLI::error( 'Source-directory regression failed.' ); }
