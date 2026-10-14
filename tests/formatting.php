<?php
/**
 * Dependency-free formatter regressions. Run with PHP 5.6 or newer.
 *
 * No WordPress bootstrap, database or network access is needed.
 */

use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\CurrencyFormat;

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/plugin/trunk/vendor/autoload.php';

$checks   = 0;
$failures = 0;

function assert_formatting_same( $expected, $actual, $label ) {
	global $checks, $failures;
	++$checks;
	if ( $expected !== $actual ) {
		++$failures;
		if ( $failures <= 12 ) {
			fwrite( STDERR, 'FAIL: ' . $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . PHP_EOL );
		}
	}
}

// Independent expected separators, in the public format-ID order.
$formats = array(
	array( ',', '.' ), array( '.', ' ' ), array( ',', ' ' ), array( '.', ',' ),
	array( '.', "'" ), array( ',', "'" ), array( '.', '' ), array( ',', '' ),
);
assert_formatting_same( count( $formats ), count( CurrencyFormat::get_list() ), 'Format inventory' );

// Expectations use format 3 notation; strtr only substitutes its separators.
$integer_cases = array(
	array( null, '0' ), array( 0, '0' ), array( 10, '10' ), array( 100, '100' ),
	array( 1000, '1,000' ), array( 1010, '1,010' ), array( 1000000, '1,000,000' ),
	array( -100, '-100' ), array( -1000, '-1,000' ), array( -1000000, '-1,000,000' ),
	array( 1234.49, '1,234' ), array( 1234.5, '1,235' ), array( -1234.5, '-1,235' ),
	array( 999.5, '1,000' ), array( -999.5, '-1,000' ),
	array( 0.49, '0' ), array( 0.5, '1' ), array( -0.5, '-1' ), array( '1000', '1,000' ),
	// PHP < 7.2 can return negative zero; preserve the runtime's rounding behavior.
	array( -0.01, PHP_VERSION_ID < 70200 ? '-0' : '0' ),
);
$fractional_cases = array(
	array( null, '0' ), array( 0, '0' ), array( 100, '100' ), array( 1000, '1,000' ),
	array( -1000, '-1,000' ), array( 12.5, '12.5' ), array( -12.5, '-12.5' ),
	array( '100.000', '100' ), array( 1.00000001, '1' ),
);
$rounded = array( 1 => '1,234.6', '1,234.57', '1,234.568', '1,234.5679', '1,234.56789', '1,234.567891', '1,234.5678913' );

foreach ( $formats as $format_id => $separators ) {
	$replace = array( '.' => $separators[0], ',' => $separators[1] );
	foreach ( $integer_cases as $case ) {
		assert_formatting_same( strtr( $case[1], $replace ), Currency::for_format( $case[0], $format_id, 0 ), 'Integer format ' . $format_id . ', value ' . var_export( $case[0], true ) );
	}
	foreach ( range( 1, 7 ) as $decimals ) {
		foreach ( $fractional_cases as $case ) {
			assert_formatting_same( strtr( $case[1], $replace ), Currency::for_format( $case[0], $format_id, $decimals ), 'Existing fractional format ' . $format_id . ', precision ' . $decimals );
		}
		assert_formatting_same( strtr( $rounded[ $decimals ], $replace ), Currency::for_format( 1234.56789126, $format_id, $decimals ), 'Rounding format ' . $format_id . ', precision ' . $decimals );
		assert_formatting_same( PHP_VERSION_ID < 70200 ? '-0' : '0', Currency::for_format( -0.000000001, $format_id, $decimals ), 'Existing negative zero, precision ' . $decimals );
	}
	foreach ( array( '0', -1, -100, null ) as $decimals ) {
		assert_formatting_same( strtr( '1,000', $replace ), Currency::for_format( 1000, $format_id, $decimals ), 'Zero precision coercion/clamp, format ' . $format_id );
	}
	foreach ( array( '7', 8, 100 ) as $decimals ) {
		assert_formatting_same( strtr( $rounded[7], $replace ), Currency::for_format( 1234.56789126, $format_id, $decimals ), 'Maximum precision clamp, format ' . $format_id );
	}
}

foreach ( array( -1, 99, array() ) as $format_id ) {
	assert_formatting_same( '1,000', Currency::for_format( 1000, $format_id, 0 ), 'Existing unknown-format fallback' );
	assert_formatting_same( '1,234.5', Currency::for_format( 1234.5, $format_id, 2 ), 'Existing fractional fallback' );
}
assert_formatting_same( '1.000', Currency::for_format( 1000, '0', '0' ), 'String format and precision from saved settings' );
assert_formatting_same( '1,234.5', Currency::for_format( 1234.5, '3', '2' ), 'Existing string settings' );

printf( "%s: %d formatting assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
exit( $failures ? 1 : 0 );
