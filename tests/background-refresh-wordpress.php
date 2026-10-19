<?php
/** Background refresh contracts. Run only in a disposable local WordPress database. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Background tests require the isolated local environment.' );
}
set_error_handler( function ( $level, $message, $file, $line ) {
	if ( error_reporting() & $level ) {
		throw new ErrorException( $message, 0, $level, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );

function background_state() {
	wp_cache_delete( 'alloptions', 'options' );
	foreach ( array( 'exchange-rates', 'exchange-rates_rates' ) as $name ) {
		wp_cache_delete( $name, 'options' );
	}
	return array( 'settings' => get_option( 'exchange-rates' ), 'rates' => get_option( 'exchange-rates_rates' ) );
}
function background_events() {
	$result = array();
	foreach ( _get_cron_array() as $timestamp => $hooks ) {
		foreach ( array( 'exchange-rates_data_update', 'exchange-rates_data_retry' ) as $hook ) {
			if ( isset( $hooks[ $hook ] ) ) {
				foreach ( $hooks[ $hook ] as $event ) {
					$result[] = array( 'hook' => $hook, 'timestamp' => $timestamp, 'schedule' => $event['schedule'] );
				}
			}
		}
	}
	return $result;
}
function background_payload( $rate ) {
	return array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
		array( 'local_time' => '2026-01-02 12:00:00', 'put_time' => '2026-01-02 12:01:00', 'rates' => array( 'USD' => 1, 'EUR' => $rate, 'GBP' => 1.5 ) ),
		array( 'local_time' => '2026-01-01 12:00:00', 'put_time' => '2026-01-01 12:01:00', 'rates' => array( 'USD' => 1, 'EUR' => 1.2, 'GBP' => 1.4 ) ),
	) );
}
function background_html() {
	$html = array();
	foreach ( array(
		'[exchange-rates_badge id="background-badge" base_currency="USD" currency_list="EUR" flag_type="none"]',
		'[exchange-rates_currency-table id="background-table" base_currency="USD" currency_list="EUR" flag_type="none"]',
		'[exchange-rates_currency-converter id="background-converter" from="USD" to="EUR"]',
	) as $shortcode ) {
		$html[] = do_shortcode( $shortcode );
	}
	foreach ( array( 'Table', 'Converter' ) as $name ) {
		ob_start();
		the_widget( '\\Dejurin\\ExchangeRates\\Widgets\\' . $name, array(), array( 'before_widget' => '<section>', 'after_widget' => '</section>' ) );
		$html[] = ob_get_clean();
	}
	return $html;
}

if ( isset( $args[0] ) && 'request' === $args[0] ) {
	$case = json_decode( base64_decode( $args[1] ), true );
	$GLOBALS['exchange_rates_test_now'] = $case['now'];
	$GLOBALS['exchange_rates_test_offset_seconds'] = 0;
	require __DIR__ . '/manual-update-clock.php';
	require __DIR__ . '/background-clock.php';
	if ( isset( $case['seed'] ) ) {
		wp_clear_scheduled_hook( 'exchange-rates_data_update' );
		wp_clear_scheduled_hook( 'exchange-rates_data_retry' );
		delete_option( 'exchange-rates_refresh_lock' );
		delete_transient( 'exchange-rates_refresh_after' );
		$settings = array( 'source_id' => 'currencyrate', 'base_currency' => 'USD', 'currency_format' => 3, 'decimals' => 4, 'rates_available' => true, 'update_timestamp' => $case['now'] - $case['seed'], 'custom_legacy_key' => 'preserve-me' );
		if ( isset( $case['unknown'] ) ) {
			if ( 'missing' === $case['unknown'] ) {
				unset( $settings['update_timestamp'] );
			} else {
				$settings['update_timestamp'] = $case['unknown'];
			}
		}
		update_option( 'exchange-rates', $settings );
		update_option( 'exchange-rates_rates', ! empty( $case['empty'] ) ? array() : background_payload( 1.25 ) );
		if ( empty( $case['no_hourly'] ) ) {
			$fixture_schedule = function ( $schedules ) {
				$schedules['exchange-rates_30_minutes'] = array( 'interval' => 1800, 'display' => 'Test interval' );
				return $schedules;
			};
			add_filter( 'cron_schedules', $fixture_schedule );
			wp_schedule_event( $case['now'] + 600, 'exchange-rates_30_minutes', 'exchange-rates_data_update' );
			remove_filter( 'cron_schedules', $fixture_schedule );
		}
	}
	if ( isset( $case['lock'] ) ) {
		update_option( 'exchange-rates_refresh_lock', $case['lock'], false );
	}
	$before = background_state();
	$requests = array();
	add_filter( 'pre_http_request', function ( $response, $http_args, $url ) use ( $case, &$requests ) {
		if ( 'https://api-bank.fex.to/rates/currencyrate.json' !== $url ) {
			return $response;
		}
		$requests[] = array( 'url' => $url, 'timeout' => $http_args['timeout'] );
		if ( ! empty( $case['replace_lock'] ) ) {
			update_option( 'exchange-rates_refresh_lock', ( $case['now'] + 600 ) . ':successor', false );
		}
		if ( ! empty( $case['hold'] ) ) {
			update_option( 'exchange_rates_test_worker_started', true );
		}
		if ( ! empty( $case['delay'] ) ) {
			usleep( $case['delay'] );
		}
		if ( 'failure' === $case['provider'] ) {
			return new WP_Error( 'background_provider_failure', 'Controlled provider failure.' );
		}
		$payload = 'malformed' === $case['provider'] ? array( 'data' => array() ) : background_payload( 2 );
		return array( 'headers' => array(), 'body' => wp_json_encode( $payload ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
	}, 30, 3 );
	$started = microtime( true );
	require WP_PLUGIN_DIR . '/exchange-rates/Install.php';
	// This child loads the plugin after core init; initialize its deferred shortcode.
	$table_shortcode = new \Dejurin\ExchangeRates\Shortcodes\CurrencyTable();
	$table_shortcode->init();
	do_action( 'widgets_init' );
	$page_elapsed = microtime( true ) - $started;
	$initial_requests = count( $requests );
	if ( in_array( $case['operation'], array( 'retry', 'hourly' ), true ) ) {
		$hook = 'retry' === $case['operation'] ? 'exchange-rates_data_retry' : 'exchange-rates_data_update';
		if ( 'retry' === $case['operation'] ) {
			$next = wp_next_scheduled( $hook );
			if ( $next ) {
				wp_unschedule_event( $next, $hook );
			}
		}
		do_action_ref_array( $hook, array() );
	} elseif ( 'manual' === $case['operation'] ) {
		\Dejurin\ExchangeRates\Service\UpdateDataSources::update();
	} elseif ( 'deactivate' === $case['operation'] ) {
		\Dejurin\ExchangeRates\Deactivation::run();
	}
	$after = background_state();
	echo wp_json_encode( array( 'before' => $before, 'after' => $after, 'requests' => $requests, 'initial_requests' => $initial_requests, 'page_elapsed' => $page_elapsed,
		'events' => background_events(), 'retry_at' => get_transient( 'exchange-rates_refresh_after' ), 'lock' => get_option( 'exchange-rates_refresh_lock' ), 'html' => background_html() ) );
	return;
}

$original = background_state();
$original_cron = get_option( 'cron' );
$original_lock = get_option( 'exchange-rates_refresh_lock' );
$original_retry = get_transient( 'exchange-rates_refresh_after' );
global $checks, $failed, $defaults;
$checks = 0;
$failed = 0;
$now = time();
$defaults = array( 'now' => $now, 'provider' => 'success', 'operation' => 'page' );
function background_check( $condition, $message ) {
	global $checks, $failed;
	++$checks;
	if ( ! $condition ) {
		++$failed;
		WP_CLI::log( 'FAIL: ' . $message );
	}
}
function background_command( $case ) {
	global $defaults;
	return '--skip-plugins --skip-themes eval-file ' . escapeshellarg( __FILE__ ) . ' request ' . escapeshellarg( base64_encode( wp_json_encode( array_merge( $defaults, $case ) ) ) );
}
function background_request( $case = array() ) {
	$r = WP_CLI::runcommand( background_command( $case ), array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$data = json_decode( $r->stdout, true );
	if ( 0 !== $r->return_code || '' !== trim( $r->stderr ) || ! is_array( $data ) ) {
		throw new RuntimeException( 'Invalid background process: ' . $r->stdout . $r->stderr );
	}
	return $data;
}
function background_retry_events( $r ) {
	return array_values( array_filter( $r['events'], function ( $e ) { return 'exchange-rates_data_retry' === $e['hook']; } ) );
}
function background_same_data( $a, $b ) {
	ksort( $a['settings'] );
	ksort( $b['settings'] );
	return $a === $b;
}
function background_process( $case ) {
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $_SERVER['argv'][0] ) . ' ' . background_command( $case );
	$pipes = array();
	$process = proc_open( $command, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	if ( ! is_resource( $process ) ) {
		throw new RuntimeException( 'Could not launch the concurrent test process.' );
	}
	fclose( $pipes[0] );
	return array( $process, $pipes );
}
function background_join( $process ) {
	$out = stream_get_contents( $process[1][1] );
	$err = stream_get_contents( $process[1][2] );
	fclose( $process[1][1] );
	fclose( $process[1][2] );
	$code = proc_close( $process[0] );
	$result = json_decode( $out, true );
	if ( 0 !== $code || '' !== trim( $err ) || ! is_array( $result ) ) {
		throw new RuntimeException( 'Concurrent test process failed: ' . $out . $err );
	}
	return $result;
}
try {
	$fresh = background_request( array( 'seed' => 10 ) );
	foreach ( $fresh['html'] as $i => $html ) {
		background_check( false !== strpos( $html, 'cr-exchange-rates' ) && false === strpos( $html, '[exchange-rates_' ), 'Renderer ' . $i . ' produces real plugin markup' );
	}
	background_check( ! $fresh['requests'] && ! background_retry_events( $fresh ), 'Fresh cached data needs no HTTP request or retry event' );
	$stale = background_request( array( 'seed' => 3700, 'provider' => 'failure', 'delay' => 250000 ) );
	background_check( ! $stale['requests'] && $stale['page_elapsed'] < 0.2, 'Expired page bootstrap must not wait for or call the provider' );
	background_check( background_same_data( $stale['before'], $stale['after'] ), 'Page rendering does not mutate saved rates/settings' );
	background_check( $fresh['html'] === $stale['html'], 'Three shortcodes and two widgets keep cached markup after expiry' );
	background_check( 1 === count( background_retry_events( $stale ) ), 'One asynchronous refresh is queued for expired data' );
	$again = background_request( array( 'provider' => 'failure' ) );
	background_check( ! $again['requests'] && $again['events'] === $stale['events'], 'Repeated page visits cannot duplicate refresh tasks' );
	$failure = background_request( array( 'operation' => 'retry', 'provider' => 'failure' ) );
	background_check( 0 === $failure['initial_requests'] && 1 === count( $failure['requests'] ), 'Only the background worker contacts the provider' );
	$expected = $stale['before'];
	$expected['settings']['rates_available'] = false;
	background_check( background_same_data( $expected, $failure['after'] ), 'Failure keeps previous rates/base/date/user settings and records unavailability' );
	background_check( $failure['html'] === $fresh['html'], 'A failed refresh does not replace usable cached widgets with an error' );
	$events = background_retry_events( $failure );
	background_check( 1 === count( $events ) && $events[0]['timestamp'] >= $now + 300, 'Failed refresh schedules a bounded retry, at least five minutes later' );
	$retry = background_request( array( 'now' => $now + 1, 'operation' => 'hourly', 'provider' => 'failure' ) );
	background_check( ! $retry['requests'], 'Even an overlapping hourly event respects failure backoff' );
	$recovery = background_request( array( 'now' => $now + 300, 'operation' => 'retry' ) );
	background_check( 1 === count( $recovery['requests'] ) && 2 === $recovery['after']['rates']['data'][0]['rates']['EUR'], 'Retry recovers after the backoff without reseeding data' );
	background_check( $now + 300 === $recovery['after']['settings']['update_timestamp'] && true === $recovery['after']['settings']['rates_available'], 'Only successful persistence advances successful UTC time' );
	background_check( ! background_retry_events( $recovery ) && ! $recovery['lock'], 'Success releases the worker lease and clears pending retry' );
	$updated_page = background_request( array( 'now' => $now + 301 ) );
	background_check( ! $updated_page['requests'] && $updated_page['html'] === $recovery['html'] && $updated_page['html'] !== $fresh['html'], 'Next page sees newly persisted rates without another HTTP call' );
	foreach ( array( 'missing', 0 ) as $unknown ) {
		$legacy = background_request( array( 'seed' => 3700, 'unknown' => $unknown, 'provider' => 'failure' ) );
		background_check( ! $legacy['requests'] && $legacy['html'] === $fresh['html'] && 1 === count( background_retry_events( $legacy ) ), 'Legacy cache with unknown successful time renders immediately and queues recovery' );
		background_check( background_same_data( $legacy['before'], $legacy['after'] ), 'Unknown legacy metadata remains unchanged during rendering' );
		$empty = background_request( array( 'seed' => 3700, 'empty' => true, 'unknown' => $unknown, 'provider' => 'failure' ) );
		background_check( ! $empty['requests'] && 1 === count( background_retry_events( $empty ) ), 'First install with unknown time queues recovery without blocking' );
		foreach ( $empty['html'] as $i => $html ) {
			background_check( false !== strpos( $html, 'exchange-rate data is not available yet' ), 'Empty cache has a clear unavailable message, renderer ' . $i );
		}
		$first = background_request( array( 'operation' => 'retry' ) );
		background_check( 1 === count( $first['requests'] ) && true === $first['after']['settings']['rates_available'], 'First installation recovers when the queued request succeeds' );
	}
	$repair = background_request( array( 'seed' => 10, 'no_hourly' => true ) );
	$repair_again = background_request();
	background_check( 1 === count( $repair['events'] ) && 'exchange-rates_30_minutes' === $repair['events'][0]['schedule'] && $repair['events'] === $repair_again['events'], 'Missing recurring schedule is restored once without fetching rates' );
	$hourly = background_request( array( 'seed' => 10, 'operation' => 'hourly' ) );
	background_check( 1 === count( $hourly['requests'] ), 'Existing hourly hook still runs its update independently of page traffic' );
	$hourly_events = array_values( array_filter( $hourly['events'], function ( $e ) { return 'exchange-rates_data_update' === $e['hook']; } ) );
	background_check( 1 === count( $hourly_events ) && 'exchange-rates_30_minutes' === $hourly_events[0]['schedule'], 'Public hook is preserved with the thirty-minute schedule' );
	background_request( array( 'seed' => 3700, 'operation' => 'retry', 'provider' => 'malformed' ) );
	$manual = background_request( array( 'now' => $now + 1, 'operation' => 'manual' ) );
	background_check( 1 === count( $manual['requests'] ) && true === $manual['after']['settings']['rates_available'], 'Explicit manual refresh is not blocked by background backoff' );
	$queued_after_manual = background_request( array( 'now' => $now + 300, 'operation' => 'retry' ) );
	background_check( ! $queued_after_manual['requests'], 'Pending retry does not refetch a cache already updated manually' );
	$successor = background_request( array( 'seed' => 10, 'operation' => 'hourly', 'replace_lock' => true ) );
	background_check( ( $now + 600 ) . ':successor' === $successor['lock'], 'An older worker cannot remove a successor lease' );
	$locked = background_request( array( 'seed' => 3700, 'lock' => ( $now + 60 ) . ':other-worker', 'operation' => 'hourly' ) );
	background_check( ! $locked['requests'] && $locked['lock'] === ( $now + 60 ) . ':other-worker', 'A live lease prevents overlapping background requests and is not removed' );
	$expired = background_request( array( 'seed' => 3700, 'lock' => ( $now - 1 ) . ':dead-worker', 'operation' => 'hourly' ) );
	background_check( 1 === count( $expired['requests'] ) && ! $expired['lock'], 'An expired worker lease is recoverable' );
	background_request( array( 'seed' => 3700, 'provider' => 'failure' ) );
	delete_option( 'exchange_rates_test_worker_started' );
	$first = background_process( array( 'operation' => 'hourly', 'delay' => 2000000, 'hold' => true ) );
	for ( $i = 0; $i < 100; ++$i ) {
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( 'exchange_rates_test_worker_started', 'options' );
		if ( get_option( 'exchange_rates_test_worker_started' ) ) {
			break;
		}
		usleep( 50000 );
	}
	background_check( $i < 100, 'Concurrent provider fixture starts before contenders' );
	$processes = array( $first, background_process( array( 'operation' => 'hourly' ) ), background_process( array( 'operation' => 'hourly' ) ) );
	$total_requests = 0;
	foreach ( $processes as $process ) {
		$result = background_join( $process );
		$total_requests += count( $result['requests'] );
	}
	background_check( 1 === $total_requests, 'Three independent concurrent workers issue exactly one provider request' );
	delete_option( 'exchange_rates_test_worker_started' );
	$clean = background_request( array( 'seed' => 3700, 'operation' => 'deactivate' ) );
	background_check( ! $clean['events'] && ! $clean['lock'] && ! $clean['retry_at'], 'Deactivation removes only background scheduling state, not cached data' );
	background_check( background_same_data( $clean['before'], $clean['after'] ), 'Deactivation preserves existing settings and cached rates' );
} finally {
	background_state();
	update_option( 'exchange-rates', $original['settings'] );
	update_option( 'exchange-rates_rates', $original['rates'] );
	wp_cache_delete( 'cron', 'options' );
	update_option( 'cron', $original_cron );
	delete_option( 'exchange-rates_refresh_lock' );
	delete_transient( 'exchange-rates_refresh_after' );
	if ( false !== $original_lock ) {
		update_option( 'exchange-rates_refresh_lock', $original_lock, false );
	}
	if ( false !== $original_retry ) {
		set_transient( 'exchange-rates_refresh_after', $original_retry, 300 );
	}
}
background_check( background_same_data( $original, background_state() ), 'Original settings and rates restored after tests' );
WP_CLI::log( sprintf( 'Background refresh regressions: %d/%d passed, %d failed.', $checks - $failed, $checks, $failed ) );
if ( $failed ) {
	WP_CLI::halt( 1 );
}
