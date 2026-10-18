<?php
/** Synthetic API payloads shared by pure PHP and isolated WordPress regressions. */
function er_validation_payload() {
	return array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
		array( 'local_time' => '2026-01-02T12:00:00+00:00', 'put_time' => '2026-01-02T12:01:00+00:00', 'rates' => array( 'USD' => 1, 'EUR' => 1.25, 'GBP' => 1.5 ) ),
		array( 'local_time' => '2026-01-01 12:00:00', 'put_time' => '2026-01-01 12:01:00', 'rates' => array( 'USD' => 1, 'EUR' => 1.2, 'GBP' => 1.4 ) ),
	) );
}
function er_validation_invalid() {
	$cases = array();
	foreach ( array( 'currencyrate', 'eu-ecb' ) as $requested ) {
		$p = er_validation_payload();
		$p['source'] = 'currencyrate' === $requested ? 'eu-ecb' : 'currencyrate';
		$cases[ 'mismatch-' . $requested ] = array( $p, $requested );
	}
	foreach ( array( '', 'unknown-source', array(), null, 123 ) as $i => $source ) {
		$p = er_validation_payload(); $p['source'] = $source;
		$cases[ 'source-' . $i ] = array( $p, 'currencyrate' );
	}
	foreach ( array( 0, -1, '1e309', '1e-400', '1e-309', 'not-a-rate', true, null, array() ) as $i => $rate ) {
		foreach ( array( 0, 1 ) as $index ) {
			$p = er_validation_payload(); $p['data'][ $index ]['rates']['EUR'] = $rate;
			$cases[ 'rate-' . $index . '-' . $i ] = array( $p, 'currencyrate' );
		}
	}
	foreach ( array( 0, 1 ) as $index ) {
		$p = er_validation_payload(); $p['data'][ $index ]['rates']['EUR'] = '1e200'; $p['data'][ $index ]['rates']['GBP'] = '1e-200';
		$cases[ 'cross-rate-overflow-' . $index ] = array( $p, 'currencyrate' );
		foreach ( array( 'local_time', 'put_time' ) as $key ) {
			$p = er_validation_payload(); unset( $p['data'][ $index ][ $key ] );
			$cases[ 'missing-' . $key . '-' . $index ] = array( $p, 'currencyrate' );
			foreach ( array( '', 'now', 'tomorrow', 'not-a-date', '2026-02-30T12:00:00Z', '2026-01-02T25:00:00Z', '2026-01-02 12:00:00 +1 day', null, 1767355200, array() ) as $i => $date ) {
				$p = er_validation_payload(); $p['data'][ $index ][ $key ] = $date;
				$cases[ 'date-' . $index . '-' . $key . '-' . $i ] = array( $p, 'currencyrate' );
			}
		}
		$p = er_validation_payload(); $p['data'][ $index ]['rates'] = 'invalid';
		$cases[ 'rates-shape-' . $index ] = array( $p, 'currencyrate' );
		$p = er_validation_payload(); unset( $p['data'][ $index ]['rates']['USD'] );
		$cases[ 'missing-base-rate-' . $index ] = array( $p, 'currencyrate' );
	}
	foreach ( array( array(), null, '', 'INVALIDBASE' ) as $i => $base ) {
		$p = er_validation_payload(); $p['base'] = $base;
		$cases[ 'base-' . $i ] = array( $p, 'currencyrate' );
	}
	$p = er_validation_payload(); $p['data'][0] = null;
	$cases['missing-current'] = array( $p, 'currencyrate' );
	return $cases;
}
function er_validation_valid() {
	$cases = array( 'two-days' => er_validation_payload() );
	$p = er_validation_payload(); unset( $p['data'][1] ); $cases['current-only'] = $p;
	$p = er_validation_payload(); $p['data'][1] = null; $cases['null-previous'] = $p;
	$p = er_validation_payload(); $p['data'][1] = array(); $cases['empty-previous'] = $p;
	$p = er_validation_payload(); $p['data'][0]['rates']['EUR'] = '1.25'; $p['data'][1]['rates']['GBP'] = '1.4e0'; $cases['numeric-strings'] = $p;
	foreach ( array( '2024-02-29', '2026-01-02 12:00:00', '2026-01-02T12:00:00Z', '2026-01-02T12:00:00.123456+02:00', 'Fri, 02 Jan 2026 12:00:00 +0000' ) as $i => $date ) {
		$p = er_validation_payload(); $p['data'][0]['local_time'] = $date; $p['data'][0]['put_time'] = $date; $cases[ 'date-format-' . $i ] = $p;
	}
	$p = er_validation_payload(); $p['source'] = 'eu-ecb'; $p['base'] = 'EUR'; $cases['ecb'] = $p;
	return $cases;
}
