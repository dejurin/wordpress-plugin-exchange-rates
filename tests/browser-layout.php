<?php
/** Read-only table layout fixtures for disposable WordPress test sites. */

if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	return;
}

add_action( 'init', function () {
	if ( ! isset( $_GET['er-layout-test'] ) ) {
		return;
	}
	$GLOBALS['wp_locale']->text_direction = isset( $_GET['direction'] ) && 'rtl' === $_GET['direction'] ? 'rtl' : 'ltr';
	add_filter( 'pre_option_exchange-rates', function () {
		return array_merge( \Dejurin\ExchangeRates\Models\Settings::get_defaults(), array( 'source_id' => 'currencyrate', 'base_currency' => 'USD', 'currency_format' => 0, 'decimals' => 2, 'rates_available' => true, 'update_timestamp' => time() ) );
	} );
	add_filter( 'pre_option_exchange-rates_rates', function () {
		$rates = array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
			array( 'local_time' => '2026-01-02 12:00:00', 'put_time' => '2026-01-02 12:01:00', 'rates' => array( 'USD' => 1, 'EUR' => 1.25, 'GBP' => 0.9, 'JPY' => 0.01, 'BRL' => 1 ) ),
			array( 'local_time' => '2026-01-01 12:00:00', 'put_time' => '2026-01-01 12:01:00', 'rates' => array( 'USD' => 1, 'EUR' => 1.2, 'GBP' => 1, 'JPY' => 0.009, 'BRL' => 1 ) ),
		) );
		if ( isset( $_GET['no-history'] ) ) {
			unset( $rates['data'][1] );
		}
		return $rates;
	} );
}, 0 );

add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['er-layout-test'] ) ) {
		return;
	}
	$common = array( 'currency_list' => 'EUR,GBP,JPY,BRL', 'base_currency' => 'USD', 'amount' => 1, 'base_show' => true, 'flag_type' => 'rectangular', 'flag_size' => 16, 'full_width' => true );
	$cases = array(
		'compact' => array( 'table_headers_show' => false, 'base_show' => false, 'border' => false, 'full_width' => false ),
		'regions' => array( 'code' => true, 'region' => true, 'table_headers_code_show' => true ),
		'full' => array( 'code' => true, 'region' => true, 'flag_type' => 'circular', 'flag_size' => 32, 'table_headers_code_show' => true, 'table_headers_previous_close_show' => true, 'table_headers_changes_show' => true ),
		'amount' => array( 'amount_active' => true, 'symbol' => true, 'after' => true, 'table_headers_previous_close_show' => true, 'table_headers_changes_show' => true ),
	);
	if ( isset( $_GET['columns'] ) ) {
		$cases = array( 'columns' => array(
			'region' => true,
			'table_headers_code_show' => ! isset( $_GET['no-code'] ),
			'table_headers_show' => ! isset( $_GET['no-headers'] ),
			'table_headers_previous_close_show' => isset( $_GET['history'] ),
			'table_headers_changes_show' => isset( $_GET['history'] ),
			'amount_active' => true,
			'symbol' => true,
			'after' => true,
		) );
	}
	if ( isset( $_GET['grid'] ) ) {
		$cases = array();
		for ( $mask = 0; $mask < 32; $mask++ ) {
			$cases[ 'grid-' . $mask ] = array( 'code' => (bool) ( $mask & 8 ), 'table_headers_show' => (bool) ( $mask & 16 ), 'table_headers_code_show' => (bool) ( $mask & 1 ), 'table_headers_previous_close_show' => (bool) ( $mask & 2 ), 'table_headers_changes_show' => (bool) ( $mask & 4 ), 'amount_active' => true, 'flag_type' => 'none' );
		}
	}
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo '<!doctype html><html lang="en" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"><head><meta name="viewport" content="width=device-width,initial-scale=1">';
	wp_head();
	echo '<style>body{margin:16px;font:15px/1.5 Arial,sans-serif}section{max-width:640px;margin-bottom:24px}</style></head><body><h1>Table layout regression fixtures</h1>';
	$number = 9000;
	foreach ( $cases as $name => $case ) {
		$attributes = array_merge( $common, $case );
		foreach ( array( 'shortcode', 'widget' ) as $kind ) {
			$id = $kind . '-' . $name;
			echo '<section id="' . esc_attr( $id ) . '"><h2>' . esc_html( $id ) . '</h2>';
			if ( 'shortcode' === $kind ) {
				$shortcode = '[exchange-rates_currency-table';
				foreach ( $attributes as $key => $value ) {
					$shortcode .= ' ' . $key . '="' . esc_attr( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '"';
				}
				echo do_shortcode( $shortcode . ']' );
			} else {
				$attributes['currency_list'] = explode( ',', $attributes['currency_list'] );
				$widget = new \Dejurin\ExchangeRates\Widgets\Table();
				$widget->_set( $number++ );
				$widget->widget( array( 'before_widget' => '<div>', 'after_widget' => '</div>' ), $attributes );
			}
			echo '</section>';
		}
	}
	wp_footer();
	echo '</body></html>';
	exit;
} );
