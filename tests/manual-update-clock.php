<?php
/** Freeze the administrative comparison and settings defaults in the isolated CLI process. */

namespace Dejurin\ExchangeRates\Admin;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== \wp_get_environment_type() ) {
	throw new \RuntimeException( 'The test clock requires the isolated local CLI environment.' );
}

function time() {
	return $GLOBALS['exchange_rates_test_now'];
}

function current_time( $type, $gmt = false ) {
	if ( 'timestamp' === $type || 'U' === $type ) {
		return time() + ( $gmt ? 0 : $GLOBALS['exchange_rates_test_offset_seconds'] );
	}
	return \current_time( $type, $gmt );
}

namespace Dejurin\ExchangeRates\Models;

function time() {
	return $GLOBALS['exchange_rates_test_now'];
}
