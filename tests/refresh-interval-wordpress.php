<?php
/** Fifteen-minute schedule regressions. Disposable synthetic WordPress only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Interval tests require the isolated local environment.' );
}
use Dejurin\ExchangeRates\Cron\UpdateRates;

set_error_handler( function ( $level, $message, $file, $line ) {
	if ( error_reporting() & $level ) {
		throw new ErrorException( $message, 0, $level, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );
$GLOBALS['exchange_rates_test_now'] = time();
require __DIR__ . '/background-clock.php';

function interval_events() {
	$events = array();
	foreach ( _get_cron_array() as $timestamp => $hooks ) {
		if ( isset( $hooks['exchange-rates_data_update'] ) ) {
			foreach ( $hooks['exchange-rates_data_update'] as $event ) {
				if ( array() === $event['args'] ) {
					$events[ $timestamp ] = $event;
				}
			}
		}
	}
	return $events;
}
if ( isset( $args[0] ) && 'worker' === $args[0] ) {
	require WP_PLUGIN_DIR . '/exchange-rates/Install.php';
	WP_CLI::log( 'Worker loaded.' );
	return;
}
$original = array();
foreach ( array( 'cron', 'exchange-rates', 'exchange-rates_rates', 'exchange-rates_refresh_lock', '_transient_exchange-rates_refresh_after', '_transient_timeout_exchange-rates_refresh_after' ) as $option ) {
	$original[ $option ] = get_option( $option );
}
global $checks, $failed;
$checks = 0;
$failed = 0;
$requests = 0;
$http = function ( $response, $arguments, $url ) use ( &$requests ) {
	if ( false !== strpos( $url, '/rates/' ) ) {
		++$requests;
	}
	return $response;
};
add_filter( 'pre_http_request', $http, 99, 3 );
function interval_check( $ok, $message ) {
	global $checks, $failed;
	++$checks;
	if ( ! $ok ) {
		++$failed;
		WP_CLI::log( 'FAIL: ' . $message );
	}
}
function interval_seed( $age = 10 ) {
	UpdateRates::clear_tasks();
	$settings = get_option( 'exchange-rates' );
	$settings['update_timestamp'] = $GLOBALS['exchange_rates_test_now'] - $age;
	update_option( 'exchange-rates', $settings );
}
function interval_valid() {
	$events = interval_events();
	$event = reset( $events );
	return 1 === count( $events ) && 'exchange-rates_15_minutes' === $event['schedule'] && 900 === $event['interval'];
}
$reject_schedule = function ( $pre, $event ) {
	return 'exchange-rates_data_update' === $event->hook ? false : $pre;
};
$reject_delete = function ( $pre, $timestamp, $hook ) {
	return 'exchange-rates_data_update' === $hook ? false : $pre;
};
try {
	$schedules = wp_get_schedules();
	interval_check( isset( $schedules['exchange-rates_15_minutes'] ) && 900 === $schedules['exchange-rates_15_minutes']['interval'], 'Plugin registers its own 900-second schedule' );
	foreach ( array( -60, 120, 3600 ) as $offset ) {
		interval_seed();
		wp_schedule_event( time() + $offset, 'hourly', 'exchange-rates_data_update' );
		$before = get_option( 'exchange-rates' );
		interval_check( UpdateRates::register_task() && interval_valid(), 'Existing hourly event migrates without reactivation: ' . $offset );
		$events = interval_events();
		interval_check( key( $events ) <= max( time(), min( time() + $offset, $GLOBALS['exchange_rates_test_now'] + 890 ) ), 'Migration does not postpone the next due refresh' );
		interval_check( $before === get_option( 'exchange-rates' ), 'Migration preserves saved settings' );
		UpdateRates::register_task();
		interval_check( $events === interval_events(), 'Repeated registration preserves timestamp and event count' );
	}
	interval_seed();
	wp_schedule_event( time() + 3600, 'hourly', 'exchange-rates_data_update' );
	wp_schedule_event( time() + 7200, 'hourly', 'exchange-rates_data_update' );
	wp_schedule_event( time() + 1800, 'hourly', 'exchange-rates_data_update', array( 'currencyrate' ) );
	$explicit = wp_get_scheduled_event( 'exchange-rates_data_update', array( 'currencyrate' ) );
	UpdateRates::register_task();
	interval_check( interval_valid(), 'Duplicate legacy default events collapse into one event' );
	interval_check( $explicit == wp_get_scheduled_event( 'exchange-rates_data_update', array( 'currencyrate' ) ), 'Explicit source events are preserved' );
	wp_clear_scheduled_hook( 'exchange-rates_data_update', array( 'currencyrate' ) );

	foreach ( array( 899, 900, 901 ) as $age ) {
		interval_seed( $age );
		// Keep the recurring event in the future to exercise only the stale-cache boundary.
		wp_schedule_event( time() + 600, 'hourly', 'exchange-rates_data_update' );
		UpdateRates::queue_if_due();
		interval_check( ( $age >= 900 ) === (bool) wp_next_scheduled( 'exchange-rates_data_retry' ), 'Stale boundary at age ' . $age );
	}
	interval_seed();
	interval_check( UpdateRates::register_task() && interval_valid(), 'Missing recurring event is restored' );
	$events = interval_events();
	wp_schedule_event( time() + 7200, 'hourly', 'exchange-rates_data_update' );
	UpdateRates::register_task();
	interval_check( interval_valid() && interval_events() === $events, 'A valid earliest event does not hide a later legacy duplicate' );

	interval_seed();
	wp_schedule_event( time() + 3600, 'hourly', 'exchange-rates_data_update' );
	$before = interval_events();
	add_filter( 'pre_schedule_event', $reject_schedule, 10, 2 );
	interval_check( false === UpdateRates::register_task(), 'Scheduling failure is reported' );
	interval_check( $before === interval_events(), 'Scheduling failure preserves the legacy event' );
	remove_filter( 'pre_schedule_event', $reject_schedule, 10 );
	interval_check( UpdateRates::register_task() && interval_valid(), 'A later request recovers from scheduling failure' );

	interval_seed();
	wp_schedule_event( time() + 3600, 'hourly', 'exchange-rates_data_update' );
	add_filter( 'pre_unschedule_event', $reject_delete, 10, 3 );
	interval_check( false === UpdateRates::register_task(), 'Legacy removal failure is reported' );
	interval_check( count( interval_events() ) >= 1, 'Removal failure leaves scheduled work available' );
	remove_filter( 'pre_unschedule_event', $reject_delete, 10 );
	interval_check( UpdateRates::register_task() && interval_valid(), 'A later request finishes interrupted migration' );


	interval_seed();
	wp_schedule_event( time() + 3600, 'hourly', 'exchange-rates_data_update' );
	$before = interval_events();
	$reject_write = function ( $value, $old_value ) { return $old_value; };
	add_filter( 'pre_update_option_cron', $reject_write, 10, 2 );
	try {
		interval_check( false === UpdateRates::register_task() && $before === interval_events(), 'Rejected cron option write preserves the legacy schedule and reports failure' );
	} finally {
		remove_filter( 'pre_update_option_cron', $reject_write, 10 );
	}
	interval_check( UpdateRates::register_task() && interval_valid(), 'Migration recovers after storage rejection' );
	$cron = _get_cron_array();
	foreach ( $cron as &$hooks ) {
		if ( isset( $hooks['exchange-rates_data_update'] ) ) {
			foreach ( $hooks['exchange-rates_data_update'] as &$event ) {
				$event['interval'] = 3600;
			}
			unset( $event );
		}
	}
	unset( $hooks );
	_set_cron_array( $cron );
	interval_check( UpdateRates::register_task() && interval_valid(), 'A matching schedule name with an obsolete stored interval is repaired' );

	interval_seed( 901 );
	wp_schedule_event( time() + 3600, 'hourly', 'exchange-rates_data_update' );
	wp_schedule_single_event( time() + 300, 'exchange-rates_data_retry' );
	set_transient( 'exchange-rates_refresh_after', time() + 300, 300 );
	$retry = wp_get_scheduled_event( 'exchange-rates_data_retry' );
	UpdateRates::register_task();
	interval_check( $retry == wp_get_scheduled_event( 'exchange-rates_data_retry' ) && get_transient( 'exchange-rates_refresh_after' ) > time(), 'Migration preserves pending retry and backoff' );

	interval_seed();
	wp_schedule_event( time() + 3600, 'hourly', 'exchange-rates_data_update' );
	$before = interval_events();
	update_option( 'exchange-rates_refresh_lock', ( time() + 300 ) . ':other', false );
	interval_check( false === UpdateRates::register_task() && $before === interval_events(), 'Busy lease defers migration without mutation' );
	update_option( 'exchange-rates_refresh_lock', ( time() - 1 ) . ':expired', false );
	interval_check( UpdateRates::register_task() && interval_valid(), 'Expired lease permits migration' );

	interval_seed();
	wp_schedule_event( time() + 3600, 'hourly', 'exchange-rates_data_update' );
	$processes = array();
	for ( $i = 0; $i < 3; ++$i ) {
		$pipes = array();
		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $_SERVER['argv'][0] ) . ' --skip-plugins --skip-themes eval-file ' . escapeshellarg( __FILE__ ) . ' worker';
		$process = proc_open( $command, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
		fclose( $pipes[0] );
		$processes[] = array( $process, $pipes );
	}
	foreach ( $processes as $process ) {
		$out = stream_get_contents( $process[1][1] );
		$err = stream_get_contents( $process[1][2] );
		fclose( $process[1][1] );
		fclose( $process[1][2] );
		interval_check( 0 === proc_close( $process[0] ) && '' === $err, 'Concurrent migration process completed: ' . trim( $out . $err ) );
	}
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'cron', 'options' );
	interval_check( interval_valid(), 'Concurrent page bootstraps leave exactly one 900-second event' );
	interval_check( 0 === $requests, 'Scheduling and stale checks never fetch rates synchronously' );
	$settings = get_option( 'exchange-rates' );
	$rates = get_option( 'exchange-rates_rates' );
	\Dejurin\ExchangeRates\Deactivation::run();
	interval_check( ! interval_events() && ! wp_next_scheduled( 'exchange-rates_data_retry' ), 'Deactivation clears both default hooks' );
	interval_check( $settings === get_option( 'exchange-rates' ) && $rates === get_option( 'exchange-rates_rates' ), 'Deactivation preserves settings and payload' );
} finally {
	remove_filter( 'pre_schedule_event', $reject_schedule, 10 );
	remove_filter( 'pre_unschedule_event', $reject_delete, 10 );
	remove_filter( 'pre_http_request', $http, 99 );
	wp_cache_delete( 'alloptions', 'options' );
	foreach ( $original as $option => $value ) {
		wp_cache_delete( $option, 'options' );
		if ( false === $value ) {
			delete_option( $option );
		} else {
			update_option( $option, $value );
		}
	}
}
WP_CLI::log( sprintf( 'Interval regressions: %d/%d passed, %d failed.', $checks - $failed, $checks, $failed ) );
if ( $failed ) {
	WP_CLI::halt( 1 );
}
