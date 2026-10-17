<?php
/** Dependency-free change regressions; PHP 5.6+, no WordPress or database. */
define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/plugin/trunk/vendor/autoload.php';

use Dejurin\ExchangeRates\Models\Currency;

$checks = 0;
$failures = 0;
function check_change( $expected, $actual, $label ) {
    global $checks, $failures;
    ++$checks;
    if ( $expected !== $actual ) {
        ++$failures;
        if ( $failures <= 16 ) {
            fwrite( STDERR, 'FAIL ' . $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . PHP_EOL );
        }
    }
}
function change_currency( $current, $previous, $amount = 1, $format = 3, $decimals = 2, $inverse = false ) {
    $class = new ReflectionClass( 'Dejurin\\ExchangeRates\\Models\\Currency' );
    $currency = $class->newInstanceWithoutConstructor();
    $values = array(
        'parameters' => array( 'amount' => $amount, 'base_currency' => 'USD', 'inverse' => $inverse, 'decimals' => $decimals ),
        'base_currency' => 'USD', 'currency' => 'JPY', 'base_currency_source' => 'USD', 'reverse' => true,
        'settings' => array( 'decimals' => $decimals, 'currency_format' => $format, 'source_id' => 'currencyrate' ),
        'rates' => array( 'base' => 'USD', 'data' => array(
            array( 'rates' => array( 'USD' => 1, 'JPY' => $current ) ),
            array( 'rates' => array( 'USD' => 1, 'JPY' => $previous ) ),
        ) ),
    );
    foreach ( $values as $name => $value ) {
        $property = $class->getProperty( $name );
        if ( PHP_VERSION_ID < 80100 ) {
            $property->setAccessible( true );
        }
        $property->setValue( $currency, $value );
    }
    return $currency;
}
// Literal expectations deliberately use previous close as the denominator.
$cases = array(
    array( 1.25, 1.2, '4.17%', 1 ),
    array( 0.9, 1, '10%', -1 ),
    array( 0.01, 0.009, '11.1%', 1 ),
    array( 0.009, 0.01, '10%', -1 ),
    array( 1.236, 1.234, '0.16%', 1 ),
    array( 2, 1, '100%', 1 ),
    array( 1, 2, '50%', -1 ),
    array( 1000000.01, 1000000, '<0.01%', 1 ),
    array( 1, 1, '–', false ),
    array( 1, null, '–', false ),
    array( 1, 0, '–', false ),
    array( null, 1, '–', false ),
);
foreach ( range( 0, 7 ) as $format ) {
    foreach ( array( 0, 2, 7 ) as $decimals ) {
        foreach ( array( 1, 100 ) as $amount ) {
            foreach ( $cases as $index => $case ) {
                $currency = change_currency( $case[0], $case[1], $amount, $format, $decimals );
                $expected = in_array( $format, array( 0, 2, 5, 7 ), true ) ? str_replace( '.', ',', $case[2] ) : $case[2];
                check_change( $expected, html_entity_decode( $currency->get_change_percentage(), ENT_QUOTES, 'UTF-8' ), 'percentage ' . $index . '/' . $format . '/' . $decimals . '/' . $amount );
                check_change( $case[3], $currency->get_trend(), 'unrounded direction ' . $index . '/' . $format . '/' . $decimals . '/' . $amount );
            }
        }
    }
}
check_change( '20%', html_entity_decode( change_currency( 1.25, 1, 1, 3, 2, true )->get_change_percentage() ), 'inverse decline' );
check_change( -1, change_currency( 1.25, 1, 1, 3, 2, true )->get_trend(), 'inverse direction' );
check_change( '0.01', change_currency( 0.01, 0.009 )->get_rate_format(), 'current rendering unchanged' );
check_change( '0.01', change_currency( 0.01, 0.009 )->get_rate_format( 1 ), 'previous rendering unchanged' );
check_change( null, change_currency( 0.01, 0.009 )->get_change_format( true ), 'legacy raw rounded-delta sentinel unchanged' );
printf( "%s: %d change assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
exit( $failures ? 1 : 0 );
