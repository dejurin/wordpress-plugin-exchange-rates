<?php
/** Request-local small-value fixtures, never mounted on an ordinary site. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) { return; }
add_action( 'init', function () {
	if ( isset( $_GET['er-small-test'] ) ) {
		$GLOBALS['wp_locale']->text_direction = isset( $_GET['direction'] ) && 'rtl' === $_GET['direction'] ? 'rtl' : 'ltr';
	}
}, 0 );
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['er-small-test'] ) ) { return; }
	$precision = isset( $_GET['precision'] ) ? max( 0, min( 7, (int) $_GET['precision'] ) ) : 4;
	$format = isset( $_GET['format'] ) ? max( 0, min( 7, (int) $_GET['format'] ) ) : 3;
	$after = isset( $_GET['after'] ) && '1' === $_GET['after'];
	$symbol = isset( $_GET['symbol'] ) && '1' === $_GET['symbol'];
	$code = $symbol ? 'EUR' : 'BTC';
	$control = $symbol ? 'GBP' : 'EUR';
	$payload = er_source_payload( 'currencyrate' );
	foreach ( array( 0, 1 ) as $index ) {
		$payload['data'][ $index ]['rates'][ $code ] = isset( $_GET['matrix'] ) ? pow( 10, -$precision ) / ( $index ? 4 : 8 ) : 0.00001234;
	}
	add_filter( 'option_exchange-rates_rates', function () use ( $payload ) { return $payload; } );
	add_filter( 'option_exchange-rates', function () use ( $precision, $format ) {
		$settings = er_source_settings( 'currencyrate' );
		$settings['decimals'] = $precision;
		$settings['currency_format'] = $format;
		return $settings;
	} );
	$attributes = array( 'amount' => 1, 'base_currency' => 'USD', 'currency_list' => $code . ',' . $control, 'amount_active' => true, 'base_show' => true, 'flag_type' => 'none', 'code' => true, 'symbol' => $symbol, 'after' => $after, 'full_width' => true );
	if ( isset( $_GET['matrix'] ) ) {
		$attributes['table_headers_previous_close_show'] = true;
		$attributes['table_headers_changes_show'] = true;
	}
	$shortcode = '[exchange-rates_currency-table';
	foreach ( $attributes as $name => $value ) {
		$shortcode .= ' ' . $name . '="' . esc_attr( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '"';
	}
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo '<!doctype html><html lang="en" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"><head><meta name="viewport" content="width=device-width,initial-scale=1">';
	wp_head();
	echo '<style>body{font-family:Arial,sans-serif;margin:20px}section{max-width:480px;margin-bottom:32px}</style></head><body>';
	echo '<section id="shortcode-table">' . do_shortcode( $shortcode . ']' ) . '</section><section id="widget-table">';
	$attributes['currency_list'] = array( $code, $control );
	the_widget( '\Dejurin\ExchangeRates\Widgets\Table', $attributes );
	$converter = array( 'amount' => 1, 'from' => 'USD', 'to' => $code, 'symbol' => $symbol, 'after' => $after );
	echo '</section><section id="shortcode-converter">';
	echo do_shortcode( '[exchange-rates_currency-converter amount="1" from="USD" to="' . $code . '" symbol="' . ( $symbol ? 'true' : 'false' ) . '" after="' . ( $after ? 'true' : 'false' ) . '"]' );
	echo '</section><section id="widget-converter">';
	the_widget( '\Dejurin\ExchangeRates\Widgets\Converter', $converter );
	echo '</section><section id="badge">';
	echo do_shortcode( '[exchange-rates_badge amount="1" base_currency="USD" currency_list="' . $code . '" flag_type="none" decimals="' . $precision . '"]' );
	echo '</section>';
	wp_footer();
	echo '</body></html>';
	exit;
}, 8 );
