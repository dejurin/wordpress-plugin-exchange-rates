<?php
/** Prepare or verify saved browser fixtures in the disposable test site only. */

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Table isolation fixtures require the isolated local test environment.' );
}

$mode = isset( $args[0] ) ? $args[0] : '';
if ( ! in_array( $mode, array( 'seed', 'verify' ), true ) ) {
	WP_CLI::error( 'Usage: wp eval-file /opt/tests/table-isolation-wordpress.php seed|verify' );
}

if ( 'seed' === $mode ) {
	$settings = get_option( Settings::$option_name );
	$settings['currency_format'] = 3;
	$settings['decimals'] = 2;
	update_option( Settings::$option_name, $settings );
	if ( ! UpdateDataSources::update() ) {
		WP_CLI::error( 'Synthetic rates could not be loaded.' );
	}
	update_option( 'widget_exchange-rates_currency-table', array(
		2 => array(
			'title' => 'Saved second table',
			'amount' => '10',
			'base_currency' => 'USD',
			'currency_list' => array( 'GBP' ),
			'flag_type' => 'none',
			'code' => true,
			'symbol' => true,
			'after' => true,
			'amount_active' => true,
			'table_headers_previous_close_show' => true,
		),
		'_multiwidget' => 1,
	) );
	$pages = array();
	foreach ( array( 'single', 'mixed', 'reversed' ) as $scenario ) {
		$slug = 'exchange-rates-table-' . $scenario;
		$page = get_page_by_path( $slug );
		$id = wp_insert_post( array(
			'ID' => $page ? $page->ID : 0,
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_name' => $slug,
			'post_title' => 'Table isolation - synthetic data - ' . $scenario,
			'post_content' => '<p>Local browser fixture. Synthetic rates only.</p>[exchange_rates_table_test mode="' . $scenario . '"]',
		), true );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( $id->get_error_message() );
		}
		$pages[ $id ] = get_post( $id )->post_content;
	}
	update_option( 'exchange_rates_table_snapshot', array(
		'options' => array(
			Settings::$option_name => get_option( Settings::$option_name ),
			'exchange-rates_rates' => get_option( 'exchange-rates_rates' ),
			'widget_exchange-rates_currency-table' => get_option( 'widget_exchange-rates_currency-table' ),
		),
		'pages' => $pages,
	) );
	WP_CLI::success( 'Three browser fixture pages and a saved widget were seeded.' );
	return;
}

$snapshot = get_option( 'exchange_rates_table_snapshot' );
if ( ! is_array( $snapshot ) ) {
	WP_CLI::error( 'Seed browser fixtures before verification.' );
}
foreach ( $snapshot['options'] as $name => $expected ) {
	if ( $expected !== get_option( $name ) ) {
		WP_CLI::error( 'Browser interaction changed stored option: ' . $name );
	}
}
foreach ( $snapshot['pages'] as $id => $content ) {
	if ( $content !== get_post( $id )->post_content ) {
		WP_CLI::error( 'Browser interaction changed saved content.' );
	}
}
WP_CLI::success( 'Settings, rates, saved widget and all three pages are unchanged.' );
