<?php
/** Cross-process cancellation regressions. Disposable local WordPress only. */
use Dejurin\ExchangeRates\Cron\UpdateRates;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Cancellation tests require the disposable local site.' );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function cancellation_child( $case ) {
	$result = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __FILE__ ) . ' child ' . escapeshellarg( base64_encode( wp_json_encode( $case ) ) ), array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$data = json_decode( $result->stdout, true );
	if ( 0 !== $result->return_code || '' !== trim( $result->stderr ) || ! is_array( $data ) ) {
		throw new RuntimeException( 'Cancellation child failed: ' . $result->stdout . $result->stderr );
	}
	return $data;
}
function cancellation_state() {
	global $wpdb;
	$state = array();
	foreach ( array( 'exchange-rates', 'exchange-rates_rates', 'cron', 'active_plugins', 'exchange-rates_refresh_lock', 'exchange-rates_refresh_generation', '_transient_exchange-rates_refresh_after', '_transient_timeout_exchange-rates_refresh_after', '_transient_exchange-rates_sources', '_transient_timeout_exchange-rates_sources' ) as $name ) {
		$state[ $name ] = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
	}
	return $state;
}
function cancellation_clear_cache() {
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	foreach ( array_keys( cancellation_state() ) as $name ) { wp_cache_delete( $name, 'options' ); }
}
function cancellation_restore( $state ) {
	cancellation_clear_cache();
	foreach ( $state as $name => $value ) {
		if ( null === $value ) { delete_option( $name ); } else { update_option( $name, maybe_unserialize( $value ) ); }
	}
}
function cancellation_events( $state ) {
	$events = array();
	$cron = maybe_unserialize( $state['cron'] );
	foreach ( is_array( $cron ) ? $cron : array() as $timestamp => $hooks ) {
		if ( ! is_array( $hooks ) ) { continue; }
		foreach ( array( 'exchange-rates_data_update', 'exchange-rates_data_retry' ) as $hook ) {
			if ( isset( $hooks[ $hook ] ) ) { $events[] = $hook; }
		}
	}
	return $events;
}

if ( isset( $args[0] ) && 'child' === $args[0] ) {
	$case = json_decode( base64_decode( $args[1] ), true );
	if ( 'reject-cancel' === $case['mode'] ) {
		$before = cancellation_state();
		add_filter( 'pre_update_option_exchange-rates_refresh_generation', function ( $value, $old ) { return $old; }, 99, 2 );
		add_filter( 'wp_die_handler', function () { return function ( $message ) { throw new RuntimeException( $message ); }; } );
		$error = null;
		try { deactivate_plugins( 'exchange-rates/Install.php' ); } catch ( RuntimeException $exception ) { $error = $exception->getMessage(); }
		echo wp_json_encode( array( 'error' => $error, 'before' => $before, 'after' => cancellation_state() ) );
		return;
	}
	if ( 'deactivate' === $case['mode'] ) {
		deactivate_plugins( 'exchange-rates/Install.php' );
		echo wp_json_encode( cancellation_state() );
		return;
	}
	if ( in_array( $case['mode'], array( 'activate', 'cycle' ), true ) ) {
		add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
			if ( 0 !== strpos( $url, 'https://api-bank.fex.to/rates/' ) ) { return $pre; }
			$payload = er_source_payload( 'currencyrate' );
			$payload['data'][0]['rates']['EUR'] = 2;
			return array( 'headers' => array(), 'body' => wp_json_encode( $payload ), 'response' => array( 'code' => 200 ), 'cookies' => array() );
		}, 99, 3 );
		if ( 'cycle' === $case['mode'] ) { deactivate_plugins( 'exchange-rates/Install.php' ); }
		$result = activate_plugin( 'exchange-rates/Install.php' );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
		echo wp_json_encode( cancellation_state() );
		return;
	}
	$requests = 0;
	$boundary = null;
	$before = cancellation_state();
	// Prime a stale option cache; cancellation must use persisted generation.
	get_option( 'exchange-rates_refresh_generation' );
	$cancel = function () use ( &$boundary, $case ) {
		if ( null !== $boundary ) { return; }
		$boundary = cancellation_child( array( 'mode' => 'deactivate' ) );
		if ( ! empty( $case['reactivate'] ) ) { $boundary = cancellation_child( array( 'mode' => 'activate' ) ); }
	};
	add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( $case, &$requests, $cancel ) {
		if ( 0 !== strpos( $url, 'https://api-bank.fex.to/rates/' ) ) { return $pre; }
		++$requests;
		if ( 'http' === $case['boundary'] ) { $cancel(); }
		if ( 'failure' === $case['outcome'] ) { return new WP_Error( 'fixture', 'Controlled provider failure.' ); }
		$payload = er_source_payload( 'currencyrate' );
		$payload['data'][0]['rates']['EUR'] = 1.5;
		return array( 'headers' => array(), 'body' => wp_json_encode( $payload ), 'response' => array( 'code' => 200 ), 'cookies' => array() );
	}, 99, 3 );
	if ( 0 === strpos( $case['boundary'], 'sql:' ) ) {
		$option = substr( $case['boundary'], 4 );
		add_filter( 'query', function ( $sql ) use ( $cancel, $option ) {
			$update = '/^UPDATE\b[\s\S]*\bWHERE\s+(?:target\.)?`?option_name`?\s*=\s*' . preg_quote( "'" . $option . "'", '/' ) . '/i';
			$insert = '/^INSERT\b[\s\S]*\bSELECT\s*' . preg_quote( "'" . $option . "'", '/' ) . '/i';
			if ( preg_match( $update, $sql ) || preg_match( $insert, $sql ) ) { $cancel(); }
			return $sql;
		}, 99 );
	}
	if ( 'before' === $case['boundary'] ) { $cancel(); }
	$result = 'hourly' === $case['mode'] ? UpdateRates::run_hourly() : UpdateDataSources::update();
	$late_callbacks = null;
	if ( 'before' === $case['boundary'] ) {
		$late_callbacks = array( UpdateRates::register_task(), UpdateRates::queue_if_due(), UpdateRates::run_retry() );
	}
	echo wp_json_encode( array( 'case' => $case, 'result' => $result, 'requests' => $requests, 'before' => $before, 'boundary' => $boundary, 'after' => cancellation_state(), 'late_callbacks' => $late_callbacks ) );
	return;
}

$original = cancellation_state();
global $checks, $failures;
$checks = 0;
$failures = array();
$reports = array();
$started = microtime( true );
function cancellation_check( $pass, $label ) {
	global $checks, $failures;
	++$checks;
	if ( ! $pass ) { $failures[] = $label; }
}
try {
	$rejected = cancellation_child( array( 'mode' => 'reject-cancel' ) );
	cancellation_check( null !== $rejected['error'], 'Cancellation storage failure is explicit' );
	cancellation_check( $rejected['before'] === $rejected['after'], 'Rejected cancellation preserves activation, cache and scheduling state' );
	$cycled = cancellation_child( array( 'mode' => 'cycle' ) );
	$cycled_rates = maybe_unserialize( $cycled['exchange-rates_rates'] );
	cancellation_check( in_array( 'exchange-rates/Install.php', maybe_unserialize( $cycled['active_plugins'] ), true ) && 2 === $cycled_rates['data'][0]['rates']['EUR'], 'Explicit same-request reactivation can refresh normally' );
	$cases = array();
	foreach ( array( 'hourly', 'direct' ) as $mode ) {
		foreach ( array( 'success', 'failure' ) as $outcome ) {
			foreach ( array( 'none', 'before', 'http', 'sql:exchange-rates' ) as $boundary ) {
				$cases[] = compact( 'mode', 'outcome', 'boundary' );
			}
			$cases[] = array( 'mode' => $mode, 'outcome' => $outcome, 'boundary' => 'http', 'reactivate' => true );
		}
		foreach ( array( false, true ) as $empty ) {
			$cases[] = array( 'mode' => $mode, 'outcome' => 'success', 'boundary' => 'sql:exchange-rates_rates', 'empty' => $empty );
		}
	}
	foreach ( $cases as $index => $case ) {
		cancellation_restore( $original );
		$settings = er_source_settings( 'currencyrate' );
		$settings['update_timestamp'] = time() - 7200;
		update_option( 'exchange-rates', $settings );
		if ( empty( $case['empty'] ) ) { update_option( 'exchange-rates_rates', er_source_payload( 'currencyrate' ) ); } else { delete_option( 'exchange-rates_rates' ); }
		UpdateRates::clear_tasks();
		$r = cancellation_child( $case );
		$reports[] = $r;
		$label = implode( '/', array( $case['mode'], $case['outcome'], $case['boundary'], ! empty( $case['reactivate'] ) ? 'reactivated' : 'inactive', ! empty( $case['empty'] ) ? 'empty' : 'cached' ) );
		cancellation_check( ( 'before' === $case['boundary'] ? 0 : 1 ) === $r['requests'], $label . ': provider count' );
		if ( 'none' === $case['boundary'] ) {
			cancellation_check( ( 'success' === $case['outcome'] ) === $r['result'], $label . ': ordinary status' );
			$rates = maybe_unserialize( $r['after']['exchange-rates_rates'] );
			cancellation_check( ( 'success' === $case['outcome'] ? 1.5 : 1.25 ) === $rates['data'][0]['rates']['EUR'], $label . ': ordinary persistence' );
			cancellation_check( ( 'hourly' === $case['mode'] && 'failure' === $case['outcome'] ) === in_array( 'exchange-rates_data_retry', cancellation_events( $r['after'] ), true ), $label . ': ordinary retry' );
		} else {
			cancellation_check( is_array( $r['boundary'] ), $label . ': cancellation reached' );
			cancellation_check( false === $r['result'], $label . ': old worker cancelled' );
			foreach ( array( 'exchange-rates', 'exchange-rates_rates', 'cron', 'active_plugins', '_transient_exchange-rates_refresh_after', '_transient_timeout_exchange-rates_refresh_after' ) as $name ) {
				if ( ! is_array( $r['boundary'] ) ) { continue; }
				cancellation_check( $r['boundary'][ $name ] === $r['after'][ $name ], $label . ': no later write to ' . $name );
			}
			if ( 'before' === $case['boundary'] ) { cancellation_check( array( false, false, false ) === $r['late_callbacks'], $label . ': already-loaded callbacks cancelled' ); }
		}
	}
} finally {
	cancellation_restore( $original );
}
cancellation_check( $original === cancellation_state(), 'Exact original test-site state restored' );
echo wp_json_encode( array( 'checks' => $checks, 'failures' => $failures, 'elapsed' => microtime( true ) - $started, 'reports' => $reports ), JSON_PRETTY_PRINT ) . PHP_EOL;
if ( $failures ) { WP_CLI::halt( 1 ); }
