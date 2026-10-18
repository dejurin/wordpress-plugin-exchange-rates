<?php
/** Clock for isolated background-refresh processes only. */
namespace Dejurin\ExchangeRates\Cron;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) ) {
	throw new \RuntimeException( 'The background clock is test-only.' );
}
function time() {
	return $GLOBALS['exchange_rates_test_now'];
}
