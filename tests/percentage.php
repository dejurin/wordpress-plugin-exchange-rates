<?php
/**
 * Public-constructor percentage regressions. Run with PHP 5.6 or newer.
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

// Numeric strings pass through the constructor and the actual RatesData reader.
$cases = array(
	array( '100', '110', '10&#x25;', 1 ),
	array( '100', '90', '10&#x25;', -1 ),
	array( '100', '100', '&ndash;', false ),
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

// Magnitude is unsigned; direction survives a rounded absolute difference.
assert_percentage_same( '12,5&#x25;', percentage_currency( 80, 90, 1, 0 )->get_change_percentage(), 'Decimal comma' );
assert_percentage_same( '1.200&#x25;', percentage_currency( 100, 1300, 1, 0 )->get_change_percentage(), 'Localized thousands separator' );
$currency = percentage_currency( 100, 100.000001 );
assert_percentage_same( '&lt;0.01&#x25;', $currency->get_change_percentage(), 'Percentage below display precision' );
assert_percentage_same( 1, $currency->get_trend(), 'Unrounded direction below absolute-change precision' );

// Both legs change: 6 / 0.75 = 8 becomes 6.6 / 0.66 = 10.
foreach ( array( true, false ) as $inverse ) {
	foreach ( array( 1, 100 ) as $amount ) {
		$GLOBALS['percentage_options'] = percentage_options( 0.75, 0.66 );
		$GLOBALS['percentage_options']['exchange-rates_rates']['data'][0]['rates']['LYD'] = 6.6;
		$GLOBALS['percentage_options']['exchange-rates_rates']['data'][1]['rates']['LYD'] = 6;
		$currency = new Currency( array( 'base_currency' => 'LYD', 'inverse' => $inverse, 'reverse' => true, 'amount' => $amount ), 'EUR' );
		$label = 'Converted pair, inverse ' . (int) $inverse . ', amount ' . $amount;
		assert_percentage_same( $inverse ? '25&#x25;' : '20&#x25;', $currency->get_change_percentage(), $label . ' magnitude' );
		assert_percentage_same( $inverse ? 1 : -1, $currency->get_trend(), $label . ' direction' );
		assert_percentage_same( true, abs( $currency->get_rate() - ( $inverse ? 10 : 0.1 ) ) < 1e-9, $label . ' current rate' );
		assert_percentage_same( true, abs( $currency->get_rate( 1 ) - ( $inverse ? 8 : 0.125 ) ) < 1e-9, $label . ' previous rate' );
	}
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
foreach ( array( true, false ) as $inverse ) {
	$currency = new Currency( array( 'base_currency' => 'UAH', 'inverse' => $inverse, 'reverse' => false, 'amount' => 1 ), 'USD' );
	$label = 'Bank source, inverse ' . (int) $inverse;
	assert_percentage_same( $inverse ? '10&#x25;' : '9.1&#x25;', $currency->get_change_percentage(), $label . ' magnitude' );
	assert_percentage_same( $inverse ? 1 : -1, $currency->get_trend(), $label . ' direction' );
	assert_percentage_same( true, abs( $currency->get_rate() - ( $inverse ? 44 : 1 / 44 ) ) < 1e-9, $label . ' current rate' );
	assert_percentage_same( true, abs( $currency->get_rate( 1 ) - ( $inverse ? 40 : 1 / 40 ) ) < 1e-9, $label . ' previous rate' );
}

// Reject either leg of either snapshot without inventing a percentage or direction.
foreach ( array( null, 0, -1, 'invalid', INF, NAN, array() ) as $invalid_index => $invalid ) {
	foreach ( array( 0, 1 ) as $index ) {
		foreach ( array( 'USD', 'EUR' ) as $code ) {
			$GLOBALS['percentage_options'] = percentage_options( 100, 110 );
			$GLOBALS['percentage_options']['exchange-rates_rates']['data'][ $index ]['rates'][ $code ] = $invalid;
			$currency = new Currency( array( 'base_currency' => 'USD', 'inverse' => false, 'reverse' => true, 'amount' => 1 ), 'EUR' );
			$label = 'Invalid value ' . $invalid_index . ', snapshot ' . $index . ', leg ' . $code;
			assert_percentage_same( '&ndash;', $currency->get_change_percentage(), $label . ' percentage' );
			assert_percentage_same( false, $currency->get_trend(), $label . ' direction' );
		}
	}
}
$currency = percentage_currency( 100, 110 );
$currency->set_currencies( 'USD', 'USD' );
assert_percentage_same( '&ndash;', $currency->get_change_percentage(), 'Base currency has no percentage change' );
assert_percentage_same( false, $currency->get_trend(), 'Base currency has no direction' );

printf( "%s: %d percentage assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
exit( $failures ? 1 : 0 );
