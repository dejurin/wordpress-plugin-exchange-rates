<?php
/** API boundary regressions; PHP 5.6+, no WordPress database or network. */
use Dejurin\ExchangeRates\Service\RatesData;
define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/plugin/trunk/vendor/autoload.php';
require __DIR__ . '/payload-validation-fixtures.php';
$checks = 0; $failures = 0;
function er_validation_check( $pass, $label ) {
	global $checks, $failures;
	++$checks;
	if ( ! $pass ) { ++$failures; fwrite( STDERR, 'FAIL: ' . $label . PHP_EOL ); }
}
set_error_handler( function ( $level, $message ) { throw new ErrorException( $message, 0, $level ); }, E_WARNING | E_NOTICE );
foreach ( er_validation_invalid() as $name => $case ) {
	try { $error = RatesData::validate_payload( $case[0], $case[1] ); er_validation_check( is_string( $error ) && '' !== $error, $name . ': reject with diagnostic' ); }
	catch ( Exception $e ) { er_validation_check( false, $name . ': unexpected warning/exception' ); }
}
foreach ( er_validation_valid() as $name => $payload ) {
	$before = serialize( $payload );
	er_validation_check( '' === RatesData::validate_payload( $payload, $payload['source'] ), $name . ': accept supported payload' );
	er_validation_check( $before === serialize( $payload ), $name . ': do not normalize or mutate payload' );
}
foreach ( array( INF, NAN, -INF, json_decode( '1e309' ) ) as $value ) {
	$p = er_validation_payload(); $p['data'][0]['rates']['EUR'] = $value;
	er_validation_check( '' !== RatesData::validate_payload( $p, 'currencyrate' ), 'Reject non-finite PHP/JSON number' );
	er_validation_check( null === RatesData::get_rate( $p, 'EUR' ), 'Unsafe cached rate is not usable' );
}
$p = er_validation_payload(); $p['source'] = 'unknown-source';
er_validation_check( '' !== RatesData::validate_payload( $p ), 'One-argument API rejects an unknown source' );
$p = er_validation_payload();
er_validation_check( '' === RatesData::validate_payload( $p ), 'One-argument validation remains supported' );
unset( $p['source'] );
er_validation_check( 'eu-ecb' === RatesData::get_source( $p, 'eu-ecb' ), 'Legacy stored source fallback is unchanged' );
er_validation_check( '2026-01-02T12:00:00+00:00' === RatesData::get_date( $p )['local_time'], 'Legacy stored dates are read unchanged' );
echo sprintf( "Payload validation: %d/%d passed, %d failed.\n", $checks - $failures, $checks, $failures );
exit( $failures ? 1 : 0 );
