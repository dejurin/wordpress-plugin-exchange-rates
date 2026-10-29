<?php
/** Read-only DOM measurements for the explicitly selected isolated comparison. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() || empty( $_GET['er_acceptance_probe'] ) ) { return; }
add_action( 'wp_footer', function () {
	$token = sanitize_text_field( wp_unslash( $_GET['er_acceptance_probe'] ) );
	if ( ! preg_match( '/^[a-f0-9-]{36}$/', $token ) ) { return; }
	$origin = isset( $_GET['er_acceptance_origin'] ) ? esc_url_raw( wp_unslash( $_GET['er_acceptance_origin'] ) ) : '';
	if ( ! preg_match( '#^http://(?:127\.0\.0\.1|localhost):[0-9]{2,5}$#', $origin ) ) { return; }
	echo '<script data-er-acceptance-origin="' . esc_attr( $origin ) . '" data-er-acceptance-probe="' . esc_attr( $token ) . '">';
	readfile( __DIR__ . '/visual-probe.js' );
	echo '</script>';
}, 999 );
