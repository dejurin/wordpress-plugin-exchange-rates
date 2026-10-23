<?php
/** Check rendered percentages without writing options in disposable WordPress. */

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Service\RatesData;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Percentage checks require the isolated local test environment.' );
}

set_error_handler( function ( $severity, $message, $file, $line ) {
	if ( error_reporting() & $severity ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR );

// WP-CLI evaluates this file inside a method; share counters explicitly.
global $percentage_checks, $percentage_failures;
$percentage_checks   = 0;
$percentage_failures = 0;
function check_rendered_percentage( $expected, $actual, $label ) {
	global $percentage_checks, $percentage_failures;
	++$percentage_checks;
	if ( $expected !== $actual ) {
		++$percentage_failures;
		WP_CLI::warning( $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
	}
}

$saved_settings = get_option( Settings::$option_name );
$saved_rates    = RatesData::get();
$settings       = function () {
	return array_merge( Settings::get_defaults(), array( 'source_id' => 'currencyrate', 'currency_format' => 3, 'decimals' => 4 ) );
};
$rates          = function () {
	return array(
		'source' => 'currencyrate',
		'base'   => 'USD',
		'data'   => array(
			array( 'rates' => array( 'USD' => 1, 'EUR' => 1.1, 'GBP' => 0.9, 'CHF' => 1 ) ),
			array( 'rates' => array( 'USD' => 1, 'EUR' => 1, 'GBP' => 1, 'CHF' => 1 ) ),
		),
	);
};
add_filter( 'pre_option_' . Settings::$option_name, $settings );
add_filter( 'pre_option_exchange-rates_rates', $rates );
try {
	$html = do_shortcode( '[exchange-rates_currency-table base_currency="USD" currency_list="EUR,GBP,CHF" amount="100" flag_type="none" code="true" table_headers_previous_close_show="true" table_headers_changes_show="true" base_show="false"]' );
} finally {
	remove_filter( 'pre_option_' . Settings::$option_name, $settings );
	remove_filter( 'pre_option_exchange-rates_rates', $rates );
}

$previous = libxml_use_internal_errors( true );
$document = new DOMDocument();
$document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET );
libxml_clear_errors();
libxml_use_internal_errors( $previous );
$xpath = new DOMXPath( $document );
$rows  = $xpath->query( '//tbody/tr' );
check_rendered_percentage( 3, $rows->length, 'All mixed-direction rows render' );
$expected = array(
	array( '110', '10%', 'up', 'Increase: 10%' ),
	array( '90', '10%', 'down', 'Decrease: 10%' ),
	array( '100', html_entity_decode( '&ndash;', ENT_QUOTES, 'UTF-8' ), null, 'No change.' ),
);
foreach ( $rows as $index => $row ) {
	$cells = $xpath->query( './td', $row );
	check_rendered_percentage( 3, $cells->length, 'Current, previous and change cells, row ' . $index );
	if ( 3 !== $cells->length || ! isset( $expected[ $index ] ) ) {
		continue;
	}
	check_rendered_percentage( $expected[ $index ][0], trim( $cells->item( 0 )->textContent ), 'Current value, row ' . $index );
	check_rendered_percentage( '100', trim( $cells->item( 1 )->textContent ), 'Previous value, row ' . $index );
	check_rendered_percentage( $expected[ $index ][1], trim( $cells->item( 2 )->textContent ), 'Changes column, row ' . $index );
	check_rendered_percentage( $expected[ $index ][3], $cells->item( 0 )->getAttribute( 'title' ), 'Percentage tooltip, row ' . $index );
	check_rendered_percentage( $expected[ $index ][3], $cells->item( 2 )->getAttribute( 'aria-label' ), 'Accessible change label, row ' . $index );
	check_rendered_percentage( $expected[ $index ][1], $cells->item( 0 )->getAttribute( 'data-change-percent' ), 'Client percentage metadata, row ' . $index );
	$trend = 'up' === $expected[ $index ][2] ? '1' : ( 'down' === $expected[ $index ][2] ? '-1' : '0' );
	check_rendered_percentage( $trend, $cells->item( 0 )->getAttribute( 'data-change-trend' ), 'Client direction metadata, row ' . $index );
	$images = $xpath->query( './/img', $cells->item( 0 ) );
	check_rendered_percentage( $expected[ $index ][2], $images->length ? $images->item( 0 )->getAttribute( 'alt' ) : null, 'Existing arrow, row ' . $index );
}
check_rendered_percentage( $saved_settings, get_option( Settings::$option_name ), 'Saved settings are unchanged' );
check_rendered_percentage( $saved_rates, RatesData::get(), 'Saved rates are unchanged' );
restore_error_handler();
if ( $percentage_failures ) {
	WP_CLI::error( $percentage_failures . ' of ' . $percentage_checks . ' rendered percentage assertions failed.' );
}
WP_CLI::success( $percentage_checks . ' rendered percentage assertions passed; no settings or rates were written.' );
