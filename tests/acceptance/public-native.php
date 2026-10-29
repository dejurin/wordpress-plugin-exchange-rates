<?php
/** Native browser runner for the disposable public-composition fixture. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) { return; }
add_action( 'template_redirect', function () {
	if ( empty( $_GET['er_public_native'] ) ) { return; }
	if ( ! in_array( parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { wp_die( 'Loopback only', '', array( 'response' => 403 ) ); }
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	if ( isset( $_GET['preview'] ) && in_array( $_GET['preview'], array( 'todayafrica', 'editorialrn', 'centralbank' ), true ) ) {
		$src = '/?er_public_case=' . rawurlencode( $_GET['preview'] ) . '&er_public_size=390';
		echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Phase 2 mobile review</title><style>body{margin:12px;font:14px system-ui}iframe{display:block;width:390px;height:780px;border:1px solid #ccd3dd;margin:12px auto}p{text-align:center}</style><p>Phase 2 · ' . esc_html( $_GET['preview'] ) . ' · 390 CSS px · browser zoom 100%</p><iframe title="Mobile composition" src="' . esc_attr( $src ) . '"></iframe></html>';
		exit;
	}
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		if ( empty( $_SERVER['HTTP_X_ACCEPTANCE_NONCE'] ) || ! wp_verify_nonce( $_SERVER['HTTP_X_ACCEPTANCE_NONCE'], 'er-public-native' ) ) { wp_send_json_error( 'Invalid nonce', 403 ); }
		$raw = file_get_contents( 'php://input', false, null, 0, 2097153 );
		$data = json_decode( $raw, true );
		if ( strlen( $raw ) > 2097152 || ! is_array( $data ) || empty( $data['browser'] ) || ! in_array( $data['browser'], array( 'safari', 'firefox' ), true ) || ! isset( $data['cases'] ) || ! is_array( $data['cases'] ) ) { wp_send_json_error( 'Invalid report', 400 ); }
		$dir = WP_CONTENT_DIR . '/public-composition-checks';
		$name = $data['browser'] . '-' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 6, false, false ) . '.json';
		if ( ! wp_mkdir_p( $dir ) || false === file_put_contents( $dir . '/' . $name, $raw, LOCK_EX ) ) { wp_send_json_error( 'Cannot save report', 500 ); }
		wp_send_json_success( array( 'file' => $name ) );
	}
	$config = array();
	foreach ( glob( '/opt/tests/acceptance/public-cases/*.json' ) as $file ) {
		$case = json_decode( file_get_contents( $file ), true );
		foreach ( array_keys( $case['environments'] ) as $width ) { $config[] = array( 'id' => $case['id'], 'surface' => $case['surface'], 'width' => (int) $width ); }
	}
	$html = file_get_contents( '/opt/tests/acceptance/public-native.html' );
	$accepted_differences = file_get_contents( '/opt/tests/acceptance/public-accepted-differences.js' );
	echo str_replace( array( '__NONCE__', '__CASES__', '__ACCEPTED_DIFFERENCES__' ), array( esc_attr( wp_create_nonce( 'er-public-native' ) ), wp_json_encode( $config ), $accepted_differences ), $html );
	exit;
}, -1000 );
