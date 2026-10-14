<?php
/** Supply a disposable administrator to the browser runner over captured stdout. */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Browser authentication requires the isolated local test environment.' );
}
if ( empty( $args[0] ) || rtrim( $args[0], '/' ) !== rtrim( home_url(), '/' ) ) {
	WP_CLI::error( 'The browser URL must match the disposable WordPress site.' );
}

$username = 'exchange_rates_browser_test';
$password = wp_generate_password( 48, true, true );
$user_id = username_exists( $username );
if ( $user_id ) {
	wp_set_password( $password, $user_id );
} else {
	$user_id = wp_create_user( $username, $password, 'browser@example.test' );
	if ( is_wp_error( $user_id ) ) {
		WP_CLI::error( 'Could not create the disposable browser administrator.' );
	}
}
$user = new WP_User( $user_id );
$user->set_role( 'administrator' );

// The runner captures this in memory. Never print it in logs or persist it in artifacts.
echo wp_json_encode( array( 'username' => $username, 'password' => $password ) );
