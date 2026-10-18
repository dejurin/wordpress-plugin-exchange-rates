<?php
/** Concurrent source-selection contracts; disposable local WordPress only. */
use Dejurin\ExchangeRates\Admin\Admin;
use Dejurin\ExchangeRates\Admin\Settings\General\Sections\Settings as SettingsForm;
use Dejurin\ExchangeRates\Cron\UpdateRates;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Concurrent source tests require the disposable local site.' );
}
if ( ! function_exists( 'er_source_payload' ) ) {
	require __DIR__ . '/source-identity-fixture.php';
}
set_error_handler( function ( $level, $message, $file, $line ) {
	if ( error_reporting() & $level ) {
		throw new ErrorException( $message, 0, $level, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );

function er_race_raw( $name ) {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
}
function er_race_state() {
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	foreach ( array( 'exchange-rates', 'exchange-rates_rates' ) as $key ) {
		wp_cache_delete( $key, 'options' );
	}
	return array( 'settings' => get_option( 'exchange-rates' ), 'rates' => get_option( 'exchange-rates_rates' ) );
}
function er_race_command( $case ) {
	return 'eval-file ' . escapeshellarg( __FILE__ ) . ' request ' . escapeshellarg( base64_encode( wp_json_encode( $case ) ) );
}
function er_race_request( $case ) {
	$r = WP_CLI::runcommand( er_race_command( $case ), array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$data = json_decode( $r->stdout, true );
	if ( 0 !== $r->return_code || '' !== trim( $r->stderr ) || ! is_array( $data ) ) {
		throw new RuntimeException( 'Invalid source-race child: ' . $r->stdout . $r->stderr );
	}
	return $data;
}

if ( isset( $args[0] ) && 'request' === $args[0] ) {
	$case = json_decode( base64_decode( $args[1] ), true );
	$requests = 0;
	$boundary = null;
	add_filter( 'pre_http_request', function ( $response, $http_args, $url ) use ( $case, &$requests ) {
		if ( 0 !== strpos( $url, 'https://api-bank.fex.to/rates/' ) ) {
			return $response;
		}
		++$requests;
		if ( ! empty( $case['hold'] ) ) {
			update_option( 'er_race_ready', true, false );
			$deadline = microtime( true ) + 20;
			while ( '1' !== er_race_raw( 'er_race_release' ) ) {
				if ( microtime( true ) > $deadline ) {
					throw new RuntimeException( 'Source-race barrier timed out.' );
				}
				usleep( 20000 );
			}
		}
		if ( 'failure' === $case['provider'] ) {
			return new WP_Error( 'controlled_source_failure', 'Synthetic source failure.' );
		}
		$source = false !== strpos( $url, '/eu-ecb.json' ) ? 'eu-ecb' : 'currencyrate';
		$payload = er_source_payload( $source );
		$payload['data'][0]['rates']['GBP'] += 0.125;
		return array( 'headers' => array(), 'body' => wp_json_encode( $payload ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
	}, 60, 3 );
	if ( isset( $case['boundary'] ) ) {
		$is_sql = 0 === strpos( $case['boundary'], 'sql:' );
		$option = $is_sql ? substr( $case['boundary'], 4 ) : $case['boundary'];
		add_filter( $is_sql ? 'query' : 'pre_update_option_' . $option, function ( $value ) use ( &$boundary, $case, $is_sql, $option ) {
			if ( $is_sql && ( ! preg_match( '/^(UPDATE|INSERT)\b/i', $value ) || false === strpos( $value, "'" . $option . "'" ) ) ) {
				return $value;
			}
			if ( null === $boundary ) {
				$boundary = er_race_request( array( 'operation' => 'switch', 'source' => 'eu-ecb', 'provider' => $case['next_provider'] ) );
			}
			return $value;
		}, 90 );
	}
	$success = null;
	if ( in_array( $case['operation'], array( 'switch', 'preferences' ), true ) ) {
		Admin::run();
		SettingsForm::register_settings();
		update_option( 'exchange-rates', array( 'source_id' => $case['source'], 'currency_format' => 0, 'decimals' => 6 ) );
	} elseif ( 'hourly' === $case['operation'] ) {
		$success = UpdateRates::run_hourly();
	} elseif ( 'direct' === $case['operation'] ) {
		$success = UpdateDataSources::update();
	}
	echo wp_json_encode( array( 'success' => $success, 'state' => er_race_state(), 'requests' => $requests, 'boundary' => $boundary ) );
	return;
}

$original = er_race_state();
$cron = get_option( 'cron' );
$lock = get_option( 'exchange-rates_refresh_lock' );
$retry = get_transient( 'exchange-rates_refresh_after' );
global $checks, $failures;
$checks = 0;
$failures = 0;
$started = microtime( true );
function er_race_check( $pass, $label ) {
	global $checks, $failures;
	++$checks;
	if ( ! $pass ) {
		++$failures;
		WP_CLI::log( 'FAIL: ' . $label );
	}
}
function er_race_seed( $source ) {
	er_race_state();
	$settings = er_source_settings( $source );
	$settings['update_timestamp'] = time() - 7200;
	update_option( 'exchange-rates', $settings );
	update_option( 'exchange-rates_rates', er_source_payload( $source ) );
	delete_option( 'exchange-rates_refresh_lock' );
	delete_transient( 'exchange-rates_refresh_after' );
	wp_clear_scheduled_hook( UpdateRates::$retry_action_name );
	delete_option( 'er_race_ready' );
	delete_option( 'er_race_release' );
}
function er_race_overlap( $old, $next, $old_provider, $new_provider, $operation = 'hourly', $change = 'switch', $empty = false ) {
	er_race_seed( $old );
	if ( $empty ) { delete_option( 'exchange-rates_rates' ); }
	$case = array( 'operation' => $operation, 'provider' => $old_provider, 'hold' => true );
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $_SERVER['argv'][0] ) . ' ' . er_race_command( $case );
	$pipes = array();
	$process = proc_open( $command, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	if ( ! is_resource( $process ) ) {
		throw new RuntimeException( 'Could not start old rate worker.' );
	}
	fclose( $pipes[0] );
	try {
		$deadline = microtime( true ) + 15;
		while ( '1' !== er_race_raw( 'er_race_ready' ) ) {
			if ( microtime( true ) > $deadline ) {
				throw new RuntimeException( 'Worker did not enter the provider.' );
			}
			usleep( 20000 );
		}
		$changed = er_race_request( array( 'operation' => $change, 'source' => $next, 'provider' => $new_provider ) );
	} finally {
		update_option( 'er_race_release', true, false );
	}
	$out = stream_get_contents( $pipes[1] );
	$err = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$code = proc_close( $process );
	$worker = json_decode( $out, true );
	if ( 0 !== $code || '' !== trim( $err ) || ! is_array( $worker ) ) {
		throw new RuntimeException( 'Old worker failed: ' . $out . $err );
	}
	$after = er_race_request( array( 'operation' => 'snapshot', 'provider' => 'success' ) );
	$label = $old . '/' . $next . '/' . $old_provider . '/' . $new_provider . '/' . $operation . '/' . $change . ( $empty ? '/empty' : '' );
	er_race_check( 1 === $worker['requests'], $label . ': one old provider request' );
	er_race_check( ( 'switch' === $change ? 1 : 0 ) === $changed['requests'], $label . ': new settings request count' );
	er_race_check( $next === $after['state']['settings']['source_id'], $label . ': selected source survives old completion' );
	er_race_check( $changed['state'] === $after['state'], $label . ': exact new rates, metadata and preferences survive' );
	er_race_check( 'preserve-me' === $after['state']['settings']['legacy_custom_key'], $label . ': legacy custom field retained' );
	$recovery = er_race_request( array( 'operation' => 'direct', 'provider' => 'success' ) );
	er_race_check( true === $recovery['success'] && $next === $recovery['state']['rates']['source'], $label . ': next ordinary refresh recovers selected source' );
}
try {
	foreach ( array( 'currencyrate' => 'eu-ecb', 'eu-ecb' => 'currencyrate' ) as $old => $next ) {
		foreach ( array( 'success', 'failure' ) as $old_provider ) {
			foreach ( array( 'success', 'failure' ) as $new_provider ) {
				er_race_overlap( $old, $next, $old_provider, $new_provider );
			}
		}
	}
	er_race_overlap( 'currencyrate', 'eu-ecb', 'success', 'success', 'direct' );
	er_race_overlap( 'currencyrate', 'currencyrate', 'success', 'success', 'hourly', 'preferences' );
	foreach ( array( 'success', 'failure' ) as $provider ) {
		er_race_overlap( 'currencyrate', 'eu-ecb', 'success', $provider, 'hourly', 'switch', true );
	}
	foreach ( array( 'exchange-rates_rates', 'exchange-rates', 'sql:exchange-rates_rates', 'sql:exchange-rates' ) as $option ) {
		foreach ( array( 'success', 'failure' ) as $provider ) {
			er_race_seed( 'currencyrate' );
			$r = er_race_request( array( 'operation' => 'direct', 'provider' => 'success', 'boundary' => $option, 'next_provider' => $provider ) );
			$after = er_race_request( array( 'operation' => 'snapshot', 'provider' => 'success' ) );
			er_race_check( is_array( $r['boundary'] ), $option . '/' . $provider . ': concurrent change executed at persistence boundary' );
			er_race_check( $r['boundary']['state'] === $after['state'], $option . '/' . $provider . ': no writes after final-check race' );
		}
	}
} finally {
	er_race_state();
	update_option( 'exchange-rates', $original['settings'] );
	update_option( 'exchange-rates_rates', $original['rates'] );
	update_option( 'cron', $cron );
	foreach ( array( 'er_race_ready', 'er_race_release' ) as $key ) {
		delete_option( $key );
	}
	if ( false === $lock ) { delete_option( 'exchange-rates_refresh_lock' ); } else { update_option( 'exchange-rates_refresh_lock', $lock, false ); }
	if ( false === $retry ) { delete_transient( 'exchange-rates_refresh_after' ); } else { set_transient( 'exchange-rates_refresh_after', $retry, 300 ); }
}
er_race_check( $original === er_race_state(), 'Original settings and rates restored' );
WP_CLI::log( sprintf( 'Concurrent source regressions: %d/%d passed, %d failed, %.1fs.', $checks - $failures, $checks, $failures, microtime( true ) - $started ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
