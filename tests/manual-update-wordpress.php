<?php
/** Exercise the shipped AJAX handler with real WordPress permissions, nonces and HTTP fixtures. */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Manual update tests require the isolated local CLI environment.' );
}

use Dejurin\ExchangeRates\Admin\Admin;
use Dejurin\ExchangeRates\Admin\Settings\General\Sections\Settings as SettingsForm;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

set_error_handler( function ( $severity, $message, $file, $line ) {
	if ( error_reporting() & $severity ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR );

if ( isset( $args[0] ) && 'request' === $args[0] ) {
	$case = json_decode( base64_decode( $args[1] ), true );
	wp_set_current_user( $case['user'] );
	add_filter( 'pre_option_timezone_string', function () use ( $case ) { return $case['zone']; } );
	add_filter( 'pre_option_gmt_offset', function ( $offset ) use ( $case ) { return '' === $case['zone'] ? $case['offset'] : $offset; } );

	// Derive the frozen offset from WordPress itself, without racing a second boundary.
	do {
		$now = time();
		$local_now = current_time( 'timestamp' );
	} while ( $now !== time() );
	$GLOBALS['exchange_rates_test_now'] = $now;
	$GLOBALS['exchange_rates_test_offset_seconds'] = $local_now - $now;
	require __DIR__ . '/manual-update-clock.php';

	$settings = wp_parse_args( get_option( Settings::$option_name ), Settings::get_defaults() );
	$settings['source_id'] = 'currencyrate';
	$settings['update_timestamp'] = $now - $case['elapsed'];
	if ( 'string' === $case['timestamp'] ) {
		$settings['update_timestamp'] = (string) $settings['update_timestamp'];
	} elseif ( 'missing' === $case['timestamp'] ) {
		unset( $settings['update_timestamp'] );
	}
	update_option( Settings::$option_name, $settings );
	$rates = get_option( UpdateDataSources::$rates_option_name );
	$rates['data'][0]['rates']['EUR'] = 2.0;
	update_option( UpdateDataSources::$rates_option_name, $rates );

	// Register the same callback and sanitizer as an administrative request.
	Admin::run();
	SettingsForm::register_settings();
	define( 'DOING_AJAX', true );
	$_REQUEST['nonce'] = wp_create_nonce( 'exchange_rates_force_data_update_nonce' );
	if ( 'invalid' === $case['nonce'] ) {
		$_REQUEST['nonce'] = 'invalid-test-nonce';
	} elseif ( 'missing' === $case['nonce'] ) {
		unset( $_REQUEST['nonce'] );
	}

	$requests = 0;
	add_filter( 'pre_http_request', function ( $response, $http_args, $url ) use ( &$requests, $case ) {
		if ( 'https://api-bank.fex.to/rates/currencyrate.json' === $url ) {
			++$requests;
			if ( 'failure' === $case['provider'] ) {
				return new WP_Error( 'test_provider_down', 'Synthetic provider failure.' );
			}
		}
		return $response;
	}, 20, 3 );

	// wp_send_json_* must terminate normally: throwing would be caught by the plugin.
	add_filter( 'wp_die_ajax_handler', function () use ( &$requests, $settings, $rates ) {
		return function ( $message = '' ) use ( &$requests, $settings, $rates ) {
			$body = ob_get_clean() . (string) $message;
			$after = get_option( Settings::$option_name );
			$user_settings = array( 'source_id', 'currency_format', 'decimals' );
			$keys = array_flip( $user_settings );
			$expected = $settings;
			if ( $requests ) {
				$expected['rates_available'] = 1.25 === get_option( UpdateDataSources::$rates_option_name )['data'][0]['rates']['EUR'];
				if ( $expected['rates_available'] ) {
					$expected['base_currency'] = 'USD';
					$expected['update_timestamp'] = $GLOBALS['exchange_rates_test_now'];
				}
			}
			echo wp_json_encode( array(
				'body' => $body,
				'requests' => $requests,
				'settings_unchanged' => $after === $settings,
				'state_saved' => $after === $expected,
				'user_settings_unchanged' => array_intersect_key( $after, $keys ) === array_intersect_key( $settings, $keys ),
				'rates_unchanged' => get_option( UpdateDataSources::$rates_option_name ) === $rates,
				'eur' => get_option( UpdateDataSources::$rates_option_name )['data'][0]['rates']['EUR'],
			) );
			exit( 0 );
		};
	} );
	ob_start();
	do_action( 'wp_ajax_exchange_rates_force_data_update' );
	ob_end_clean();
	WP_CLI::error( 'The AJAX callback did not terminate.' );
}

$users = array();
foreach ( array( 'administrator', 'subscriber' ) as $role ) {
	$username = 'exchange_rates_cooldown_' . $role;
	$user_id = username_exists( $username );
	if ( ! $user_id ) {
		$user_id = wp_create_user( $username, wp_generate_password( 48, true, true ) );
		if ( is_wp_error( $user_id ) ) {
			WP_CLI::error( 'Could not create a disposable test user.' );
		}
	}
	$user = new WP_User( $user_id );
	$user->set_role( $role );
	$users[ $role ] = $user_id;
}

$defaults = array( 'zone' => '', 'offset' => 0, 'elapsed' => 61, 'timestamp' => 'integer', 'nonce' => 'valid', 'provider' => 'success', 'user' => $users['administrator'] );
$cases = array();
foreach ( array( 'UTC' => 0, 'UTC+3' => 3, 'UTC-5' => -5, 'UTC+5:45' => 5.75 ) as $zone => $offset ) {
	foreach ( array( 0, 59, 60, 61 ) as $elapsed ) {
		$cases[ $zone . ' / ' . $elapsed . ' seconds' ] = array( 'offset' => $offset, 'elapsed' => $elapsed );
	}
}
// Older supported PHP releases ship tzdb versions with the historical Kyiv identifier.
$kyiv_zone = in_array( 'Europe/Kyiv', timezone_identifiers_list(), true ) ? 'Europe/Kyiv' : 'Europe/Kiev';
WP_CLI::log( 'Kyiv timezone identifier in this runtime: ' . $kyiv_zone );
foreach ( array( $kyiv_zone, 'America/New_York' ) as $zone ) {
	$cases[ $zone . ' / 59 seconds' ] = array( 'zone' => $zone, 'elapsed' => 59 );
	$cases[ $zone . ' / 60 seconds' ] = array( 'zone' => $zone, 'elapsed' => 60 );
}
$cases['Stored numeric string'] = array( 'offset' => 3, 'elapsed' => 59, 'timestamp' => 'string' );
$cases['Future timestamp retains existing wait'] = array( 'elapsed' => -30 );
$cases['Missing timestamp allows the first update'] = array( 'timestamp' => 'missing' );
$cases['Subscriber denied before cooldown'] = array( 'user' => $users['subscriber'], 'elapsed' => 59 );
$cases['Invalid nonce denied before cooldown'] = array( 'nonce' => 'invalid', 'elapsed' => 59 );
$cases['Missing nonce denied before cooldown'] = array( 'nonce' => 'missing', 'elapsed' => 59 );
$cases['Provider failure after cooldown'] = array( 'offset' => -5, 'provider' => 'failure' );
$cases['Provider is not contacted during cooldown'] = array( 'offset' => 3, 'elapsed' => 59, 'provider' => 'failure' );

$snapshot = array(
	Settings::$option_name => get_option( Settings::$option_name ),
	UpdateDataSources::$rates_option_name => get_option( UpdateDataSources::$rates_option_name ),
);
$failed = 0;
$started = microtime( true );
try {
	foreach ( $cases as $name => $overrides ) {
		$case = array_merge( $defaults, $overrides );
		$nonce_denied = 'valid' !== $case['nonce'];
		$capability_denied = $case['user'] !== $users['administrator'];
		$elapsed = $case['elapsed'];
		$blocked = $elapsed < 60;
		$expected_requests = ( $nonce_denied || $capability_denied || $blocked ) ? 0 : 1;
		$success = 1 === $expected_requests && 'success' === $case['provider'];
		if ( $nonce_denied ) {
			$expected_body = '-1';
		} else {
			$data = 'Successful updating data.';
			if ( $capability_denied ) {
				$data = array( 'message' => 'Access denied!' );
			} elseif ( $blocked ) {
				$data = 'Please wait before updating again. Timeout: ' . ( 60 - $elapsed ) . ' second(s).';
			} elseif ( ! $success ) {
				$data = 'Unable to update exchange-rate data. Please try again later.';
			}
			$expected_body = wp_json_encode( array( 'success' => $success, 'data' => $data ) );
		}
		$result = WP_CLI::runcommand(
			'eval-file ' . escapeshellarg( __FILE__ ) . ' request ' . escapeshellarg( base64_encode( wp_json_encode( $case ) ) ),
			array( 'return' => 'all', 'exit_error' => false, 'launch' => true )
		);
		$actual = json_decode( $result->stdout, true );
		$passed = 0 === $result->return_code && '' === trim( $result->stderr ) && is_array( $actual )
			&& $expected_body === $actual['body'] && $expected_requests === $actual['requests']
			&& $actual['user_settings_unchanged']
			&& $actual['state_saved']
			&& ( $success ? ( ! $actual['rates_unchanged'] && 1.25 === $actual['eur'] ) : $actual['rates_unchanged'] );
		if ( ! $passed ) {
			++$failed;
			WP_CLI::log( 'FAIL: ' . $name . '; expected body=' . $expected_body . '; actual=' . $result->stdout . '; stderr=' . $result->stderr );
		} else {
			WP_CLI::log( 'PASS: ' . $name );
		}
	}
} finally {
	foreach ( $snapshot as $option => $value ) {
		// Child processes have their own option caches and may have changed the database.
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( $option, 'options' );
		update_option( $option, $value );
	}
}
foreach ( $snapshot as $option => $value ) {
	if ( get_option( $option ) !== $value ) {
		WP_CLI::error( 'Could not restore the disposable fixture snapshot.' );
	}
}
WP_CLI::log( sprintf( 'Manual update regressions: %d/%d passed, %d failed, %.1fs; original settings and rates restored.', count( $cases ) - $failed, count( $cases ), $failed, microtime( true ) - $started ) );
if ( $failed ) {
	WP_CLI::halt( 1 );
}
