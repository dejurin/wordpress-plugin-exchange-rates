<?php
/** Synthetic source identity fixtures; isolated local WordPress only. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	return;
}
function er_source_payload( $source ) {
	return array( 'source' => $source, 'base' => 'currencyrate' === $source ? 'USD' : 'EUR', 'data' => array(
		array( 'local_time' => '2026-01-02 12:00:00', 'put_time' => '2026-01-02 12:01:00', 'rates' => 'currencyrate' === $source ? array( 'USD' => 1, 'EUR' => 1.25, 'GBP' => 1.5 ) : array( 'EUR' => 1, 'USD' => 2, 'GBP' => 3 ) ),
		array( 'local_time' => '2026-01-01 12:00:00', 'put_time' => '2026-01-01 12:01:00', 'rates' => 'currencyrate' === $source ? array( 'USD' => 1, 'EUR' => 1.2, 'GBP' => 1.4 ) : array( 'EUR' => 1, 'USD' => 1.5, 'GBP' => 2.5 ) ),
	) );
}
function er_source_settings( $source ) {
	return wp_parse_args( array( 'source_id' => $source, 'base_currency' => 'currencyrate' === $source ? 'USD' : 'EUR', 'update_timestamp' => time(), 'rates_available' => true, 'currency_format' => 3, 'decimals' => 2, 'legacy_custom_key' => 'preserve-me' ), \Dejurin\ExchangeRates\Models\Settings::get_defaults() );
}
function er_source_render() {
	$result = array();
	foreach ( array( 'normal' => 'false', 'inverse' => 'true' ) as $name => $inverse ) {
		$result[ 'table-' . $name ] = do_shortcode( '[exchange-rates_currency-table id="source-table-' . $name . '" base_currency="USD" currency_list="EUR,GBP" amount="1" flag_type="none" code="true" amount_active="true" inverse="' . $inverse . '" table_headers_previous_close_show="true" table_headers_changes_show="true"]' );
		$result[ 'badge-' . $name ] = do_shortcode( '[exchange-rates_badge id="source-badge-' . $name . '" base_currency="USD" currency_list="EUR" amount="1" flag_type="none" code="true" inverse="' . $inverse . '"]' );
	}
	$result['converter'] = do_shortcode( '[exchange-rates_currency-converter id="source-converter" amount="1" from="USD" to="EUR"]' );
	foreach ( array( 'table-widget' => 'Table', 'converter-widget' => 'Converter' ) as $name => $class ) {
		$class = '\\Dejurin\\ExchangeRates\\Widgets\\' . $class;
		$widget = new $class();
		$widget->number = 22;
		ob_start();
		$widget->widget( array( 'before_widget' => '', 'after_widget' => '' ), array( 'amount' => 1, 'base_currency' => 'USD', 'currency_list' => array( 'EUR', 'GBP' ), 'from' => 'USD', 'to' => 'EUR', 'flag_type' => 'none', 'code' => true, 'amount_active' => true ) );
		$result[ $name ] = ob_get_clean();
	}
	return $result;
}
add_action( 'init', function () {
	if ( isset( $_GET['er-source-test'] ) ) {
		$GLOBALS['wp_locale']->text_direction = isset( $_GET['direction'] ) && 'rtl' === $_GET['direction'] ? 'rtl' : 'ltr';
	}
}, 0 );
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['er-source-test'] ) ) { return; }
	$source = isset( $_GET['source'] ) && 'eu-ecb' === $_GET['source'] ? 'eu-ecb' : 'currencyrate';
	$mismatch = isset( $_GET['mismatch'] ) && '1' === $_GET['mismatch'];
	add_filter( 'option_exchange-rates_rates', function () use ( $source ) { return er_source_payload( $source ); } );
	add_filter( 'option_exchange-rates', function () use ( $source, $mismatch ) {
		$settings = er_source_settings( $source );
		if ( $mismatch ) {
			$settings['source_id'] = 'currencyrate' === $source ? 'eu-ecb' : 'currencyrate';
			$settings['rates_available'] = false;
			// Also cover metadata from a competing successful source update.
			$settings['base_currency'] = 'currencyrate' === $source ? 'EUR' : 'USD';
		}
		return $settings;
	} );
	$html = er_source_render();
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo '<!doctype html><html lang="en" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"><head><meta name="viewport" content="width=device-width, initial-scale=1">';
	wp_head();
	echo '<style>body{font-family:Arial,sans-serif;margin:12px}section{max-width:900px;margin-bottom:28px}</style></head><body><h1>Cached source identity</h1><p>Local synthetic data only.</p>';
	foreach ( $html as $id => $content ) { echo '<section id="' . esc_attr( $id ) . '"><h2>' . esc_html( $id ) . '</h2>' . $content . '</section>'; }
	wp_footer();
	echo '</body></html>';
	exit;
} );
