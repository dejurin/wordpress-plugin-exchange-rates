<?php
/**
 * Dependency-free percentage regressions. Run with PHP 5.6 or newer.
 *
 * WordPress options are simulated in memory; no database or network is used.
 */

use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\Settings;

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/plugin/trunk/vendor/autoload.php';

function get_option( $name, $default = false ) {
	return isset( $GLOBALS['percentage_options'][ $name ] ) ? $GLOBALS['percentage_options'][ $name ] : $default;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, $args );
}

$checks   = 0;
$failures = 0;

function assert_percentage_same( $expected, $actual, $label ) {
	global $checks, $failures;
	++$checks;
	if ( $expected !== $actual ) {
		++$failures;
		if ( $failures <= 12 ) {
			fwrite( STDERR, 'FAIL: ' . $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . PHP_EOL );
		}
	}
}

function percentage_options( $previous, $current, $format = 3 ) {
	return array(
		Settings::$option_name => array( 'currency_format' => $format, 'decimals' => 4 ),
		'exchange-rates_rates'  => array(
			'source' => 'currencyrate',
			'base'   => 'USD',
			'data'   => array(
				array( 'rates' => array( 'USD' => 1, 'EUR' => $current ) ),
				array( 'rates' => array( 'USD' => 1, 'EUR' => $previous ) ),
			),
		),
	);
}

function percentage_currency( $previous, $current, $amount = 1, $format = 3 ) {
	$GLOBALS['percentage_options'] = percentage_options( $previous, $current, $format );
	return new Currency( array( 'base_currency' => 'USD', 'inverse' => false, 'reverse' => true, 'amount' => $amount ), 'EUR' );
}

// Literal expectations use the previous value as the reference point.
$cases = array(
	array( 100, 110, '+10&#x25;', 1 ),
	array( 100, 90, '-10&#x25;', -1 ),
	array( 80, 90, '+12.5&#x25;', 1 ),
	array( 100, 200, '+100&#x25;', 1 ),
	array( 200, 100, '-50&#x25;', -1 ),
	array( 100, 1, '-99&#x25;', -1 ),
	array( 100, 1300, '+1,200&#x25;', 1 ),
	array( 100, 100, '&ndash;', false ),
	array( 100, 101.25, '+1.25&#x25;', 1 ),
	array( 100, 98.75, '-1.25&#x25;', -1 ),
	array( 100, 109.16, '+9.2&#x25;', 1 ),
	array( 100, 90.84, '-9.2&#x25;', -1 ),
	array( 100, 199.6, '+100&#x25;', 1 ),
	array( 1, 1.0001, '+0.01&#x25;', 1 ),
	array( 1, 0.9999, '-0.01&#x25;', -1 ),
	array( '100', '110', '+10&#x25;', 1 ),
);
foreach ( $cases as $case ) {
	foreach ( array( 1, 100 ) as $amount ) {
		$currency = percentage_currency( $case[0], $case[1], $amount );
		$label    = $case[0] . ' -> ' . $case[1] . ', amount ' . $amount;
		assert_percentage_same( $case[2], $currency->get_change_percentage(), $label . ' percentage' );
		assert_percentage_same( $case[3], $currency->get_trend(), $label . ' existing trend' );
		assert_percentage_same( true, abs( $currency->get_rate() - $case[1] ) < 1e-9, $label . ' current rate' );
		assert_percentage_same( true, abs( $currency->get_rate( 1 ) - $case[0] ) < 1e-9, $label . ' previous rate' );
	}
}

// Keep localized separators and the existing sub-precision placeholder.
assert_percentage_same( '+12,5&#x25;', percentage_currency( 80, 90, 1, 0 )->get_change_percentage(), 'Decimal comma' );
assert_percentage_same( '+1.200&#x25;', percentage_currency( 100, 1300, 1, 0 )->get_change_percentage(), 'Localized thousands separator' );
assert_percentage_same( '&ndash;', percentage_currency( 100, 100.000001 )->get_change_percentage(), 'Percentage below display precision' );

// Both legs change: 6 / 0.75 = 8 becomes 6.6 / 0.66 = 10.
foreach ( array( true, false ) as $inverse ) {
	$GLOBALS['percentage_options'] = percentage_options( 0.75, 0.66 );
	$GLOBALS['percentage_options']['exchange-rates_rates']['data'][0]['rates']['LYD'] = 6.6;
	$GLOBALS['percentage_options']['exchange-rates_rates']['data'][1]['rates']['LYD'] = 6;
	$currency = new Currency( array( 'base_currency' => 'LYD', 'inverse' => $inverse, 'reverse' => true, 'amount' => 1 ), 'EUR' );
	assert_percentage_same( $inverse ? '+25&#x25;' : '-20&#x25;', $currency->get_change_percentage(), 'Converted pair, inverse ' . (int) $inverse );
	assert_percentage_same( $inverse ? 1 : -1, $currency->get_trend(), 'Converted pair direction, inverse ' . (int) $inverse );
}

// Bank sources use the other rate orientation; 40 becomes 44 local units per USD.
$GLOBALS['percentage_options'] = percentage_options( 1, 1 );
$GLOBALS['percentage_options']['exchange-rates_rates'] = array(
	'source' => 'ua-nbu',
	'base'   => 'UAH',
	'data'   => array(
		array( 'rates' => array( 'UAH' => 1, 'USD' => 44 ) ),
		array( 'rates' => array( 'UAH' => 1, 'USD' => 40 ) ),
	),
);
$currency = new Currency( array( 'base_currency' => 'UAH', 'inverse' => true, 'reverse' => false, 'amount' => 1 ), 'USD' );
assert_percentage_same( '+10&#x25;', $currency->get_change_percentage(), 'Bank source orientation' );

foreach ( array( array( null, 110 ), array( 0, 110 ), array( -1, 110 ), array( 'invalid', 110 ), array( 100, null ), array( 100, 0 ), array( 100, -1 ) ) as $case ) {
	$currency = percentage_currency( $case[0], $case[1] );
	assert_percentage_same( '&ndash;', $currency->get_change_percentage(), 'Missing or invalid rate: ' . json_encode( $case ) );
}
$currency = percentage_currency( 100, 110 );
$currency->set_currencies( 'USD', 'USD' );
assert_percentage_same( '&ndash;', $currency->get_change_percentage(), 'Base currency has no percentage change' );

printf( "%s: %d percentage assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
exit( $failures ? 1 : 0 );
