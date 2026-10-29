<?php
/** Plugin Name: Isolated table fitting proposal */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) { return; }
if ( empty( $_GET['er_table_fit'] ) || ! in_array( $_GET['er_table_fit'], array( 'review', 'before', 'after' ), true ) ) { return; }
if ( ! in_array( parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { return; }

add_action( 'template_redirect', function () {
	nocache_headers();
	if ( 'review' !== $_GET['er_table_fit'] ) { return; }
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		if ( empty( $_SERVER['HTTP_X_ACCEPTANCE_NONCE'] ) || ! wp_verify_nonce( $_SERVER['HTTP_X_ACCEPTANCE_NONCE'], 'er-table-fit-preview' ) ) { wp_send_json_error( 'Invalid nonce', 403 ); }
		$raw = file_get_contents( 'php://input', false, null, 0, 2097153 );
		$data = json_decode( $raw, true );
		if ( strlen( $raw ) > 2097152 || ! is_array( $data ) || ! isset( $data['cases'], $data['userAgent'] ) || ! is_array( $data['cases'] ) ) { wp_send_json_error( 'Invalid report', 400 ); }
		$dir = WP_CONTENT_DIR . '/table-fit-preview';
		$name = 'review-' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 6, false, false ) . '.json';
		if ( ! wp_mkdir_p( $dir ) || false === file_put_contents( $dir . '/' . $name, $raw, LOCK_EX ) ) { wp_send_json_error( 'Cannot save report', 500 ); }
		wp_send_json_success( array( 'file' => $name ) );
	}
	$html = file_get_contents( __DIR__ . '/table-fit-preview.html' );
	echo str_replace( array( '__NONCE__', '__RUNNER__', '__BASELINE__' ), array( esc_attr( wp_create_nonce( 'er-table-fit-preview' ) ), file_get_contents( __DIR__ . '/table-fit-preview.js' ), wp_json_encode( json_decode( file_get_contents( '/acceptance/table-fit-baseline.json' ), true )['cases'] ) ), $html );
	exit;
} );

if ( 'review' === $_GET['er_table_fit'] ) { return; }

add_action( 'init', function () {
	add_shortcode( 'er_theme_lab', function () {
		$variant = isset( $_GET['fit_case'] ) ? sanitize_key( $_GET['fit_case'] ) : 'history';
		$args = array( 'title' => '', 'id' => 'fit-table', 'base_currency' => 'USD', 'currency_list' => 'EUR,GBP,JPY', 'code' => true, 'flag_type' => 'none', 'border' => false, 'amount_active' => true, 'table_headers_previous_close_show' => true, 'table_headers_changes_show' => true );
		if ( 'standard' === $variant ) { $args['table_headers_previous_close_show'] = false; $args['table_headers_changes_show'] = false; }
		if ( 'names' === $variant ) { $args['code'] = false; }
		if ( 'flags' === $variant ) { $args['flag_type'] = 'rectangular'; }
		if ( 'large' === $variant ) { $args['amount'] = 1234567.89; }
		if ( 'native' === $variant ) {
			$args['currency_list'] = explode( ',', $args['currency_list'] );
			ob_start();
			the_widget( '\\Dejurin\\ExchangeRates\\Widgets\\Table', $args );
			$html = ob_get_clean();
		} else {
			$shortcode = '[exchange-rates_currency-table';
			foreach ( $args as $key => $value ) { $shortcode .= ' ' . $key . '="' . esc_attr( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '"'; }
			$html = do_shortcode( $shortcode . ']' );
		}
		if ( 'standard' !== $variant ) { $html = str_replace( 'table-responsive ', 'table-responsive er-table-fit-history ', $html ); }
		return '<section id="lab-fixture">' . $html . '</section>';
	} );
}, 20 );
