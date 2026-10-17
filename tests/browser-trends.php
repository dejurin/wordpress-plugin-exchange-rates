<?php
/** Read-only trend fixtures; never load outside a disposable local test site. */

if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	return;
}

add_action( 'init', function () {
	if ( ! isset( $_GET['er-trend-test'] ) ) {
		return;
	}
	$GLOBALS['wp_locale']->text_direction = isset( $_GET['direction'] ) && 'rtl' === $_GET['direction'] ? 'rtl' : 'ltr';
	add_filter( 'pre_option_exchange-rates', function () {
		return array_merge( \Dejurin\ExchangeRates\Models\Settings::get_defaults(), array( 'source_id' => 'currencyrate', 'base_currency' => 'USD', 'currency_format' => isset( $_GET['changes'], $_GET['format'] ) ? max( 0, min( 7, (int) $_GET['format'] ) ) : 3, 'decimals' => isset( $_GET['changes'] ) && isset( $_GET['precision'] ) ? max( 0, min( 7, (int) $_GET['precision'] ) ) : 2, 'rates_available' => true, 'update_timestamp' => time() ) );
	} );
	add_filter( 'pre_option_exchange-rates_rates', function () {
        if ( isset( $_GET['changes'] ) ) {
            return array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
                array( 'rates' => array( 'USD' => 1, 'EUR' => 1.25, 'GBP' => 0.9, 'AUD' => 1.236, 'JPY' => 0.01, 'BRL' => 1, 'CAD' => 1, 'CHF' => 1000000.01 ) ),
                array( 'rates' => array( 'USD' => 1, 'EUR' => 1.2, 'GBP' => 1, 'AUD' => 1.234, 'JPY' => 0.009, 'BRL' => 1, 'CHF' => 1000000 ) ),
            ) );
        }
		return array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
			array( 'local_time' => '2026-01-02 12:00:00', 'put_time' => '2026-01-02 12:01:00', 'rates' => array( 'USD' => 1, 'EUR' => 1.25, 'GBP' => 1.5, 'AUD' => 0.75, 'BRL' => 0.2, 'JPY' => 0.01 ) ),
			array( 'local_time' => '2026-01-01 12:00:00', 'put_time' => '2026-01-01 12:01:00', 'rates' => array( 'USD' => 1, 'EUR' => 1, 'GBP' => 2, 'AUD' => 0.75, 'BRL' => 0.25 ) ),
		) );
	} );
}, 0 );

add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['er-trend-test'] ) ) {
		return;
	}
	$after = isset( $_GET['after'] ) && '1' === $_GET['after'];
	$inverse = isset( $_GET['inverse'] ) && '1' === $_GET['inverse'];
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo '<!doctype html><html lang="en" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"><head><meta name="viewport" content="width=device-width, initial-scale=1">';
	wp_head();
	// Simulate common theme image rules without replacing plugin markup or styles.
	echo '<style>body{margin:16px;font-family:Arial,sans-serif;font-size:15px}section{max-width:640px;margin-bottom:24px}img{max-width:100%;height:auto;width:32px}td img{margin:0}</style></head><body>';
	echo '<h1>Trend arrow regression fixture</h1><p>Local synthetic rates: growth, decline, unchanged and missing previous data.</p>';
	foreach ( array( true, false ) as $full_width ) {
		$attributes = array( 'amount' => 1, 'base_currency' => 'USD', 'currency_list' => isset( $_GET['changes'] ) ? 'EUR,GBP,AUD,JPY,BRL,CAD,CHF' : 'EUR,GBP,AUD,BRL,JPY', 'base_show' => true, 'amount_active' => true, 'code' => true, 'flag_type' => 'rectangular', 'flag_size' => 16, 'full_width' => $full_width, 'inverse' => $inverse, 'table_headers_previous_close_show' => true, 'table_headers_changes_show' => isset( $_GET['changes'] ) );
		$suffix = $full_width ? 'full' : 'compact';
		$shortcode = '[exchange-rates_currency-table';
		foreach ( $attributes as $name => $value ) {
			$shortcode .= ' ' . $name . '="' . esc_attr( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '"';
		}
		echo '<section id="shortcode-' . $suffix . '"><h2>Shortcode: ' . $suffix . '</h2>' . do_shortcode( $shortcode . ']' ) . '</section>';
		echo '<section id="widget-' . $suffix . '"><h2>Native widget: ' . $suffix . '</h2>';
		$attributes['currency_list'] = explode( ',', $attributes['currency_list'] );
		$attributes['symbol'] = true;
		$attributes['after'] = $after;
		the_widget( '\Dejurin\ExchangeRates\Widgets\Table', $attributes );
		echo '</section>';
	}
	wp_footer();
	echo '</body></html>';
	exit;
} );
