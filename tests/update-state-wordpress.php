<?php
/** Persisted update-state regressions; run only in the disposable synthetic WordPress site. */

use Dejurin\ExchangeRates\Admin\Admin;
use Dejurin\ExchangeRates\Admin\Settings\General\Sections\Settings as SettingsForm;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Update-state tests require the disposable local environment.' );
}
set_error_handler( function ( $severity, $message, $file, $line ) {
	if ( error_reporting() & $severity ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR );

function update_state_options() {
	wp_cache_delete( 'alloptions', 'options' );
	foreach ( array( Settings::$option_name, UpdateDataSources::$rates_option_name ) as $name ) {
		wp_cache_delete( $name, 'options' );
	}
	return array( 'settings' => get_option( Settings::$option_name ), 'rates' => get_option( UpdateDataSources::$rates_option_name ) );
}

if ( isset( $args[0] ) && 'request' === $args[0] ) {
	$case = json_decode( base64_decode( $args[1] ), true );
	$GLOBALS['exchange_rates_test_now'] = $case['now'];
	$GLOBALS['exchange_rates_test_offset_seconds'] = (int) ( $case['offset'] * HOUR_IN_SECONDS );
	require __DIR__ . '/manual-update-clock.php';
	add_filter( 'pre_option_timezone_string', function () { return ''; } );
	add_filter( 'pre_option_gmt_offset', function () use ( $case ) { return $case['offset']; } );
	wp_set_current_user( $case['user'] );
	$requests = array();
	add_filter( 'pre_http_request', function ( $response, $http_args, $url ) use ( &$requests, $case ) {
		if ( ! in_array( $url, array( 'https://api-bank.fex.to/rates/currencyrate.json', 'https://api-bank.fex.to/rates/eu-ecb.json' ), true ) ) {
			return $response;
		}
		$stored = get_option( Settings::$option_name, array() );
		$requests[] = array( 'url' => $url, 'stored_source' => isset( $stored['source_id'] ) ? $stored['source_id'] : null );
		if ( 'failure' === $case['provider'] ) {
			return new WP_Error( 'test_provider_failure', 'Synthetic provider failure.' );
		}
		if ( 'malformed' === $case['provider'] ) {
			$payload = array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array() );
		} elseif ( false !== strpos( $url, '/eu-ecb.json' ) ) {
			$payload = array( 'source' => 'eu-ecb', 'base' => 'EUR', 'data' => array( array( 'local_time' => '2026-01-02 12:00:00', 'put_time' => '2026-01-02 12:01:00', 'rates' => array( 'EUR' => 1, 'USD' => 2, 'GBP' => 3 ) ) ) );
		} else {
			return $response;
		}
		return array( 'headers' => array(), 'body' => wp_json_encode( $payload ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
	}, 30, 3 );
	// Bootstrap fixtures must be installed after WordPress loads, before exercising Plugin::run().
	if ( isset( $case['seed'] ) ) {
		update_option( Settings::$option_name, $case['seed'] );
	}
	if ( $case['admin'] ) {
		Admin::run();
		SettingsForm::register_settings();
	}
	if ( isset( $case['reject_write'] ) ) {
		$option = 'rates' === $case['reject_write'] ? UpdateDataSources::$rates_option_name : Settings::$option_name;
		add_filter( 'pre_update_option_' . $option, function ( $value, $old ) { return $old; }, 100, 2 );
	}
	$finish = function ( $body ) use ( &$requests ) {
		echo wp_json_encode( array( 'body' => $body, 'requests' => $requests, 'state' => update_state_options() ) );
	};
	if ( 'ajax' === $case['operation'] ) {
		define( 'DOING_AJAX', true );
		$_REQUEST['nonce'] = wp_create_nonce( 'exchange_rates_force_data_update_nonce' );
		add_filter( 'wp_die_ajax_handler', function () use ( $finish ) {
			return function ( $message = '' ) use ( $finish ) {
				$finish( json_decode( ob_get_clean() . (string) $message, true ) );
				exit( 0 );
			};
		} );
		ob_start();
		do_action( 'wp_ajax_exchange_rates_force_data_update' );
		WP_CLI::error( 'AJAX did not terminate.' );
	} elseif ( 'form' === $case['operation'] ) {
		update_option( Settings::$option_name, $case['input'] );
		$finish( null );
	} elseif ( 'cron' === $case['operation'] ) {
		do_action_ref_array( \Dejurin\ExchangeRates\Cron\UpdateRates::$action_name, array() );
		$finish( null );
	} elseif ( 'bootstrap' === $case['operation'] ) {
		$GLOBALS['dejurin_exchange_rates']->run();
		$finish( null );
	} elseif ( 'render' === $case['operation'] ) {
		ob_start();
		\Dejurin\ExchangeRates\Admin\Settings\General\Page::render();
		$finish( ob_get_clean() );
	} elseif ( 'service-form' === $case['operation'] ) {
		$status = UpdateDataSources::update();
		update_option( Settings::$option_name, $case['input'] );
		$finish( $status );
	} else {
		$finish( UpdateDataSources::update( isset( $case['force_source'] ) ? $case['force_source'] : null ) );
	}
	return;
}

$user = username_exists( 'exchange_rates_state_administrator' );
if ( ! $user ) {
	$user = wp_create_user( 'exchange_rates_state_administrator', wp_generate_password( 48, true, true ) );
}
if ( is_wp_error( $user ) ) {
	WP_CLI::error( 'Could not create the disposable administrator.' );
}
$administrator = new WP_User( $user );
$administrator->set_role( 'administrator' );
global $update_state_snapshot, $update_state_defaults, $update_state_checks, $update_state_failures;
$update_state_snapshot = update_state_options();
$now = time();
$update_state_defaults = array( 'operation' => 'ajax', 'now' => $now, 'offset' => 0, 'provider' => 'success', 'admin' => true, 'user' => $user );
$update_state_checks = 0;
$update_state_failures = 0;
$started = microtime( true );

function update_state_check( $condition, $label ) {
	global $update_state_checks, $update_state_failures;
	++$update_state_checks;
	if ( ! $condition ) {
		++$update_state_failures;
		WP_CLI::log( 'FAIL: ' . $label );
	}
}
function update_state_request( $overrides = array() ) {
	global $update_state_defaults;
	$case = array_merge( $update_state_defaults, $overrides );
	$result = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __FILE__ ) . ' request ' . escapeshellarg( base64_encode( wp_json_encode( $case ) ) ), array( 'return' => 'all', 'exit_error' => false, 'launch' => true ) );
	$data = json_decode( $result->stdout, true );
	if ( 0 !== $result->return_code || '' !== trim( $result->stderr ) || ! is_array( $data ) ) {
		throw new RuntimeException( 'Invalid child result: ' . $result->stdout . $result->stderr );
	}
	return $data;
}
function update_state_seed( $timestamp ) {
	global $update_state_snapshot;
	update_state_options();
	$settings = array( 'source_id' => 'currencyrate', 'currency_format' => 3, 'decimals' => 2, 'base_currency' => 'GBP', 'rates_available' => false, 'custom_legacy_key' => 'preserve-me' );
	if ( null !== $timestamp ) {
		$settings['update_timestamp'] = $timestamp;
	}
	update_option( Settings::$option_name, $settings );
	$rates = $update_state_snapshot['rates'];
	$rates['data'][0]['rates']['EUR'] = 2;
	update_option( UpdateDataSources::$rates_option_name, $rates );
	return update_state_options();
}
function update_state_success( $result, $now, $base, $source, $label ) {
	$settings = $result['state']['settings'];
	update_state_check( isset( $settings['update_timestamp'] ) && $now === $settings['update_timestamp'], $label . ': persisted success timestamp' );
	update_state_check( true === $settings['rates_available'] && $base === $settings['base_currency'], $label . ': persisted availability and base' );
	update_state_check( $base === $result['state']['rates']['base'] && $source === $result['state']['rates']['source'], $label . ': rates and metadata agree' );
	update_state_check( 3 === $settings['currency_format'] && 2 === $settings['decimals'] && 'preserve-me' === $settings['custom_legacy_key'], $label . ': saved user and unknown legacy fields preserved' );
}

try {
	foreach ( array( 0, 3, -5, 5.75 ) as $offset ) {
		foreach ( array( 'integer' => $now - 61, 'string' => (string) ( $now - 61 ), 'missing' => null, 'zero' => 0 ) as $kind => $timestamp ) {
			update_state_seed( $timestamp );
			$label = $offset . '/' . $kind;
			$first = update_state_request( array( 'offset' => $offset ) );
			update_state_check( true === $first['body']['success'] && 1 === count( $first['requests'] ), $label . ': first request succeeds' );
			update_state_success( $first, $now, 'USD', 'currencyrate', $label );
			// Each request loads the real state left by the preceding request. Never reseed inside this loop.
			foreach ( array( 1, 59, 60, 61 ) as $elapsed ) {
				$next = update_state_request( array( 'offset' => $offset, 'now' => $now + $elapsed ) );
				$allowed = 60 === $elapsed;
				update_state_check( $allowed === $next['body']['success'] && ( $allowed ? 1 : 0 ) === count( $next['requests'] ), $label . ': repeat at +' . $elapsed );
				if ( ! $allowed ) {
					update_state_check( $first['state'] === $next['state'], $label . ': denied repeat leaves all stored data unchanged' );
				} else {
					update_state_success( $next, $now + 60, 'USD', 'currencyrate', $label . '/second-success' );
					$first = $next;
				}
			}
		}
	}
	WP_CLI::log( 'Sequential request matrix complete; checking failures and save contexts.' );
	foreach ( array( 'failure', 'malformed' ) as $provider ) {
		update_state_seed( $now - 61 );
		$success = update_state_request();
		$failure = update_state_request( array( 'now' => $now + 60, 'provider' => $provider ) );
		$expected = $success['state'];
		$expected['settings']['rates_available'] = false;
		update_state_check( false === $failure['body']['success'] && 1 === count( $failure['requests'] ), $provider . ': failure reported' );
		update_state_check( $expected === $failure['state'], $provider . ': last successful data/base/time retained, availability cleared' );
	}
	foreach ( array( 'service-admin' => array( 'operation' => 'service' ), 'service-cli' => array( 'operation' => 'service', 'admin' => false ), 'cron' => array( 'operation' => 'cron', 'admin' => false ) ) as $name => $case ) {
		update_state_seed( $now - 61 );
		$result = update_state_request( $case );
		update_state_success( $result, $now, 'USD', 'currencyrate', $name );
		$form = update_state_request( array( 'operation' => 'form', 'input' => array( 'source_id' => 'currencyrate', 'currency_format' => 0, 'decimals' => 5, 'base_currency' => 'JPY', 'update_timestamp' => $now + 99999, 'rates_available' => false, 'injected_key' => 'reject-me' ) ) );
		$expected = $result['state'];
		$expected['settings']['currency_format'] = 0;
		$expected['settings']['decimals'] = 5;
		update_state_check( $expected === $form['state'] && ! $form['requests'], $name . ': form changes only user fields and rejects injected metadata' );
	}
	foreach ( array( 'rates', 'metadata' ) as $target ) {
		$before = update_state_seed( $now - 61 );
		$result = update_state_request( array( 'reject_write' => $target ) );
		update_state_check( false === $result['body']['success'], 'Rejected ' . $target . ' write must not report success' );
		update_state_check( $before['settings']['update_timestamp'] === $result['state']['settings']['update_timestamp'], 'Rejected ' . $target . ' write must not advance successful time' );
	}
	update_state_seed( $now - 61 );
	$form = update_state_request( array( 'operation' => 'service-form', 'input' => array( 'source_id' => 'currencyrate', 'currency_format' => 3, 'decimals' => 2, 'base_currency' => 'JPY', 'update_timestamp' => $now + 99999, 'rates_available' => false ) ) );
	update_state_success( $form, $now, 'USD', 'currencyrate', 'form after service in the same request' );
	update_state_check( true === $form['body'] && 1 === count( $form['requests'] ), 'Same-request form cannot inject service metadata or trigger another fetch' );
	update_state_seed( $now - 61 );
	$forced = update_state_request( array( 'operation' => 'service', 'force_source' => 'eu-ecb' ) );
	update_state_success( $forced, $now, 'EUR', 'eu-ecb', 'explicit source argument' );
	update_state_check( 'currencyrate' === $forced['state']['settings']['source_id'], 'Explicit source argument retains the saved source selection' );
	$invalid = update_state_request( array( 'operation' => 'service', 'force_source' => 'invalid-test-source' ) );
	$expected = $forced['state'];
	$expected['settings']['rates_available'] = false;
	update_state_check( false === $invalid['body'] && ! $invalid['requests'] && $expected === $invalid['state'], 'Unknown source preserves cached data/time and clears availability without HTTP' );
	update_state_seed( null );
	delete_option( Settings::$option_name );
	$created = update_state_request();
	update_state_check( true === $created['body']['success'] && 1 === count( $created['requests'] ), 'Missing option permits the first authorized update' );
	update_state_check( is_array( $created['state']['settings'] ) && isset( $created['state']['settings']['update_timestamp'] ) && $now === $created['state']['settings']['update_timestamp'] && true === $created['state']['settings']['rates_available'] && 'USD' === $created['state']['settings']['base_currency'], 'Initial add_option path persists all service fields' );
	foreach ( array( 'success', 'failure' ) as $provider ) {
		$before = update_state_seed( $now - 61 );
		$result = update_state_request( array( 'operation' => 'form', 'provider' => $provider, 'input' => array( 'source_id' => 'eu-ecb', 'currency_format' => 3, 'decimals' => 2 ) ) );
		update_state_check( 1 === count( $result['requests'] ) && 'eu-ecb' === $result['requests'][0]['stored_source'], 'source change/' . $provider . ': fetch runs once after selected source is stored' );
		if ( 'success' === $provider ) {
			update_state_success( $result, $now, 'EUR', 'eu-ecb', 'source change' );
		} else {
			$before['settings']['source_id'] = 'eu-ecb';
			// wp_parse_args() may reorder associative settings; compare every value and type.
			ksort( $before['settings'] );
			ksort( $result['state']['settings'] );
			update_state_check( $before === $result['state'], 'source change failure: cached rates and last success metadata preserved' );
		}
	}
	foreach ( array( null, 0 ) as $timestamp ) {
		$before = update_state_seed( $timestamp );
		$result = update_state_request( array( 'operation' => 'bootstrap', 'admin' => false, 'seed' => $before['settings'] ) );
		update_state_check( ! $result['requests'] && $before === $result['state'], 'Unknown time must not trigger refresh on every page bootstrap' );
		$render_settings = $before['settings'];
		$render_settings['rates_available'] = true;
		$html = update_state_request( array( 'operation' => 'render', 'seed' => $render_settings ) );
		update_state_check( false === strpos( $html['body'], 'Last successful update:' ), 'Unknown success time is not rendered as now or 1970' );
	}
	$before = update_state_seed( $now - 61 );
	// Do not let plugin bootstrap refresh an expired fixture before the child installs its clock.
	$before['settings']['update_timestamp'] = $now - HOUR_IN_SECONDS - 1;
	$result = update_state_request( array( 'operation' => 'bootstrap', 'admin' => false, 'seed' => $before['settings'] ) );
	update_state_check( ! $result['requests'], 'Expired bootstrap queues background work without provider HTTP' );
	update_state_check( $before === $result['state'], 'Expired bootstrap preserves saved data while the background job is pending' );
	update_state_seed( null );
	$form = update_state_request( array( 'operation' => 'form', 'input' => array( 'source_id' => 'currencyrate', 'currency_format' => 3, 'decimals' => 2 ) ) );
	update_state_check( empty( $form['state']['settings']['update_timestamp'] ), 'Saving the form does not fabricate a successful update time' );
} finally {
	update_state_options();
	update_option( Settings::$option_name, $update_state_snapshot['settings'] );
	update_option( UpdateDataSources::$rates_option_name, $update_state_snapshot['rates'] );
}
if ( $update_state_snapshot !== update_state_options() ) {
	WP_CLI::error( 'Could not restore original settings and rates.' );
}
WP_CLI::log( sprintf( 'Update state regressions: %d/%d passed, %d failed, %.1fs; original options restored.', $update_state_checks - $update_state_failures, $update_state_checks, $update_state_failures, microtime( true ) - $started ) );
if ( $update_state_failures ) {
	WP_CLI::halt( 1 );
}
