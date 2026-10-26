<?php
/** Public rate-display boundaries; PHP 5.6+, no WordPress or network. */
use Dejurin\ExchangeRates\Models\Currency;

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/plugin/trunk/vendor/autoload.php';
function get_option( $name, $default = false ) {
	return isset( $GLOBALS['small_options'][ $name ] ) ? $GLOBALS['small_options'][ $name ] : $default;
}
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, $args ); }

$checks = 0;
$failures = 0;
function small_check( $expected, $actual, $label ) {
	global $checks, $failures;
	++$checks;
	if ( $expected !== $actual ) {
		++$failures;
		if ( $failures <= 12 ) { fwrite( STDERR, 'FAIL: ' . $label . ': ' . var_export( $actual, true ) . ' != ' . var_export( $expected, true ) . PHP_EOL ); }
	}
}
foreach ( range( 0, 7 ) as $format ) {
	$point = in_array( $format, array( 0, 2, 5, 7 ), true ) ? ',' : '.';
	foreach ( range( 0, 7 ) as $precision ) {
		$unit = pow( 10, -$precision );
		$limit = $precision ? '0' . $point . str_repeat( '0', $precision - 1 ) . '1' : '1';
		$GLOBALS['small_options'] = array(
			'exchange-rates' => array( 'currency_format' => $format, 'decimals' => $precision ),
			'exchange-rates_rates' => array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
				array( 'rates' => array( 'USD' => 1, 'BTC' => $unit / 8 ) ),
				array( 'rates' => array( 'USD' => 1, 'BTC' => $unit / 4 ) ),
			) ),
		);
		foreach ( array( 1 => '&lt;' . $limit, -1 => '&gt;-' . $limit, 0 => '0', 8 => $limit, 16 => str_replace( '1', '2', $limit ) ) as $amount => $expected ) {
			$currency = new Currency( array( 'base_currency' => 'USD', 'inverse' => false, 'reverse' => true, 'amount' => $amount, 'decimals' => $precision ), 'BTC' );
			small_check( $expected, $currency->get_rate_format(), "format $format, precision $precision, amount $amount" );
			if ( 1 === $amount ) {
				small_check( '&lt;' . $limit, $currency->get_rate_format( 1 ), 'Previous close remains a nonzero bound' );
				small_check( '&lt;' . $limit, $currency->get_rate_format( 0, true, true ), 'Badge precision path' );
			}
		}
		$missing = new Currency( array( 'base_currency' => 'USD', 'inverse' => false, 'reverse' => true, 'amount' => 1 ), 'XYZ' );
		small_check( '&ndash;', $missing->get_rate_format(), 'Missing rate remains unavailable' );
	}
}
// Arithmetic loss of representability must not be mistaken for genuine zero.
$GLOBALS['small_options']['exchange-rates']['currency_format'] = 3;
$GLOBALS['small_options']['exchange-rates']['decimals'] = 4;
$GLOBALS['small_options']['exchange-rates_rates']['data'][0]['rates']['BTC'] = 0.25;
foreach ( array( 5e-324, INF, NAN ) as $amount ) {
	$currency = new Currency( array( 'base_currency' => 'USD', 'inverse' => false, 'reverse' => true, 'amount' => $amount ), 'BTC' );
	small_check( '&ndash;', $currency->get_rate_format(), 'Unrepresentable result remains unavailable' );
}
$currency = new Currency( array( 'base_currency' => 'USD', 'inverse' => false, 'reverse' => true, 'amount' => -0.0 ), 'BTC' );
small_check( '0', $currency->get_rate_format(), 'Actual negative zero is normalized' );
printf( "%s: %d small-value assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
exit( $failures ? 1 : 0 );
