<?php
/** Native WP-Cron retry ownership regressions. Disposable synthetic WordPress only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Native cron tests require the isolated local environment.' );
}

use Dejurin\ExchangeRates\Cron\UpdateRates;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

function native_retry_events() {
	$events = array();
	foreach ( ( _get_cron_array() ?: array() ) as $timestamp => $hooks ) {
		foreach ( array( UpdateRates::$action_name, UpdateRates::$retry_action_name ) as $hook ) {
			if ( isset( $hooks[ $hook ] ) ) {
				foreach ( $hooks[ $hook ] as $event ) {
					$events[] = array( 'hook' => $hook, 'timestamp' => (int) $timestamp, 'schedule' => $event['schedule'], 'interval' => isset( $event['interval'] ) ? $event['interval'] : null );
				}
			}
		}
	}
	return $events;
}

if ( isset( $args[0] ) && 'worker' === $args[0] ) {
	$case = $args[1];
	if ( ! in_array( $case, array( 'recurring-first', 'retry-first', 'fresh-retries', 'future-retry', 'failure' ), true ) ) {
		throw new RuntimeException( 'Unknown native cron scenario.' );
	}
	// Only this disposable database is mutated; the parent restores its full snapshot.
	_set_cron_array( array() );
	delete_transient( 'doing_cron' );
	delete_option( 'exchange-rates_refresh_lock' );
	delete_transient( 'exchange-rates_refresh_after' );
	$settings = get_option( 'exchange-rates' );
	$settings['source_id'] = 'currencyrate';
	update_option( 'exchange-rates', $settings );
	if ( ! UpdateDataSources::update() ) {
		throw new RuntimeException( 'Could not seed synthetic rates.' );
	}
	$now = time();
	$settings = get_option( 'exchange-rates' );
	$settings['update_timestamp'] = 'fresh-retries' === $case ? $now : $now - 3600;
	update_option( 'exchange-rates', $settings );
	$before = array( 'settings' => get_option( 'exchange-rates' ), 'rates' => get_option( 'exchange-rates_rates' ) );
	$scheduled = array();
	if ( 'fresh-retries' === $case ) {
		$scheduled[] = wp_schedule_single_event( $now - 1200, UpdateRates::$retry_action_name );
		// Seed multiple overdue jobs directly; scheduling rejects already-overdue duplicates.
		$cron = _get_cron_array();
		$cron[ $now - 1 ] = $cron[ $now - 1200 ];
		$scheduled[] = _set_cron_array( $cron );
	} else {
		if ( 'retry-first' === $case ) {
			$scheduled[] = wp_schedule_single_event( $now - 1, UpdateRates::$retry_action_name );
		}
		$scheduled[] = wp_schedule_event( $now - 1, UpdateRates::SCHEDULE, UpdateRates::$action_name );
		if ( 'retry-first' !== $case ) {
			$scheduled[] = wp_schedule_single_event( 'future-retry' === $case ? $now + 300 : $now - 1, UpdateRates::$retry_action_name );
		}
	}
	if ( in_array( false, $scheduled, true ) ) {
		throw new RuntimeException( 'Could not schedule the native cron scenario: ' . $case );
	}
	$requests = 0;
	$callbacks = array();
	$missing = array();
	add_filter( 'pre_http_request', function ( $response, $arguments, $url ) use ( $case, &$requests ) {
		if ( 'https://api-bank.fex.to/rates/currencyrate.json' === $url ) {
			++$requests;
			if ( 'failure' === $case ) {
				return new WP_Error( 'test_provider_down', 'Synthetic provider failure.' );
			}
		}
		return $response;
	}, 99, 3 );
	foreach ( array( UpdateRates::$action_name, UpdateRates::$retry_action_name ) as $hook ) {
		add_action( $hook, function () use ( $hook, &$callbacks ) { $callbacks[] = $hook; }, 1 );
	}
	// Observe the real core API without short-circuiting it, including on WP 5.6.
	add_filter( 'pre_unschedule_event', function ( $pre, $timestamp, $hook, $event_args ) use ( &$missing ) {
		if ( UpdateRates::$retry_action_name === $hook ) {
			$cron = _get_cron_array();
			if ( ! isset( $cron[ $timestamp ][ $hook ][ md5( serialize( $event_args ) ) ] ) ) {
				$missing[] = array( 'timestamp' => $timestamp, 'hook' => $hook );
			}
		}
		return $pre;
	}, 10, 4 );
	$log = tempnam( sys_get_temp_dir(), 'er-native-cron-' );
	if ( false === $log ) {
		throw new RuntimeException( 'Could not create the diagnostic log.' );
	}
	ini_set( 'error_log', $log );
	register_shutdown_function( function () use ( $case, $now, $before, &$requests, &$callbacks, &$missing, $log ) {
		$result = array( 'case' => $case, 'now' => $now, 'before' => $before,
			'after' => array( 'settings' => get_option( 'exchange-rates' ), 'rates' => get_option( 'exchange-rates_rates' ) ),
			'requests' => $requests, 'callbacks' => $callbacks, 'missing_removals' => $missing,
			'events' => native_retry_events(), 'retry_at' => get_transient( 'exchange-rates_refresh_after' ),
			'lease' => get_option( 'exchange-rates_refresh_lock' ), 'cron_lock' => get_transient( 'doing_cron' ),
			'log' => file_get_contents( $log ) );
		unlink( $log );
		echo wp_json_encode( $result ) . "\n";
	} );
	// Core owns the ready-event snapshot, unscheduling, callbacks and cron lock.
	require ABSPATH . 'wp-cron.php';
	return;
}

$original = array();
foreach ( array( 'cron', 'exchange-rates', 'exchange-rates_rates', 'exchange-rates_refresh_lock', '_transient_exchange-rates_refresh_after', '_transient_timeout_exchange-rates_refresh_after', '_transient_doing_cron', '_transient_timeout_doing_cron' ) as $option ) {
	$original[ $option ] = get_option( $option );
}
global $checks, $failed;
$checks = 0;
$failed = 0;
function native_retry_check( $condition, $message ) {
	global $checks, $failed;
	++$checks;
	if ( ! $condition ) {
		++$failed;
		WP_CLI::log( 'FAIL: ' . $message );
	}
}
try {
	foreach ( array( 'recurring-first', 'retry-first', 'fresh-retries', 'future-retry', 'failure' ) as $case ) {
		$command = 'eval-file ' . escapeshellarg( __FILE__ ) . ' worker ' . escapeshellarg( $case );
		$process = WP_CLI::runcommand( $command, array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
		$r = json_decode( $process->stdout, true );
		if ( 0 !== $process->return_code || '' !== trim( $process->stderr ) || ! is_array( $r ) ) {
			throw new RuntimeException( 'Native cron worker failed: ' . $process->stdout . $process->stderr );
		}
		native_retry_check( ! $r['missing_removals'], $case . ': core never removes an event already deleted by a callback' );
		native_retry_check( '' === trim( $r['log'] ), $case . ': native wp-cron.php produces no error log: ' . trim( $r['log'] ) );
		native_retry_check( ! $r['lease'] && ! $r['cron_lock'], $case . ': worker and core leases are released' );
		$expected_requests = 'fresh-retries' === $case ? 0 : ( 'retry-first' === $case ? 2 : 1 );
		native_retry_check( $expected_requests === $r['requests'], $case . ': provider request count preserves public recurring/retry behavior' );
		native_retry_check( ( 'future-retry' === $case ? 1 : 2 ) === count( $r['callbacks'] ), $case . ': every due native callback executes' );
		$retries = array_values( array_filter( $r['events'], function ( $event ) { return UpdateRates::$retry_action_name === $event['hook']; } ) );
		$recurring = array_values( array_filter( $r['events'], function ( $event ) { return UpdateRates::$action_name === $event['hook']; } ) );
		if ( 'fresh-retries' === $case ) {
			native_retry_check( $r['before'] === $r['after'] && ! $r['events'], 'Already-fresh overdue retries finish without modifying data or leaving jobs' );
		} else {
			native_retry_check( 1 === count( $recurring ) && 1800 === $recurring[0]['interval'] && $recurring[0]['timestamp'] > $r['now'], $case . ': one thirty-minute recurring event remains' );
			if ( 'failure' === $case ) {
				native_retry_check( $r['before']['rates'] === $r['after']['rates'] && $r['before']['settings']['update_timestamp'] === $r['after']['settings']['update_timestamp'] && ! $r['after']['settings']['rates_available'], 'Failure preserves cached data/time and records unavailability' );
				native_retry_check( 1 === count( $retries ) && $retries[0]['timestamp'] >= $r['now'] + 300 && $r['retry_at'] >= $r['now'] + 300, 'Overlapping failure callbacks retain one bounded five-minute retry' );
			} else {
				native_retry_check( $r['after']['settings']['rates_available'] && $r['after']['settings']['update_timestamp'] >= $r['now'] && ! $r['retry_at'], $case . ': successful rates and UTC time persist without failure backoff' );
				native_retry_check( ( 'future-retry' === $case ? 1 : 0 ) === count( $retries ), $case . ': core consumes due retries and leaves a future retry to check freshness at its scheduled time' );
			}
		}
	}
} finally {
	foreach ( $original as $option => $value ) {
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( $option, 'options' );
		if ( false === $value ) {
			delete_option( $option );
		} else {
			update_option( $option, $value );
		}
	}
}
WP_CLI::log( sprintf( 'Native cron retry regressions: %d/%d passed, %d failed.', $checks - $failed, $checks, $failed ) );
if ( $failed ) {
	WP_CLI::halt( 1 );
}
