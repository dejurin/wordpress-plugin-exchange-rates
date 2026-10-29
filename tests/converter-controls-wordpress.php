<?php
/** Verify translated, escaped converter labels through both public renderers. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Converter checks require the isolated test environment.' );
}
if ( ! function_exists( 'er_source_payload' ) ) {
	require_once __DIR__ . '/source-identity-fixture.php';
}

$saved = array();
foreach ( array( 'exchange-rates', 'exchange-rates_rates', 'widget_exchange-rates_currency-converter' ) as $name ) {
	$saved[ $name ] = get_option( $name );
}
$settings = function () { return er_source_settings( 'currencyrate' ); };
$rates = function () { return er_source_payload( 'currencyrate' ); };
add_filter( 'pre_option_exchange-rates', $settings );
add_filter( 'pre_option_exchange-rates_rates', $rates );
$checks = 0;
$failures = 0;
$check = function ( $pass, $label ) use ( &$checks, &$failures ) {
	++$checks;
	if ( ! $pass ) {
		++$failures;
		WP_CLI::warning( $label );
	}
};
$labels = array(
	'en_US' => array( 'Amount', 'From currency', 'To currency', 'Swap currencies' ),
	'uk' => array( 'Кількість', 'Початкова валюта', 'Цільова валюта', 'Поміняти валюти місцями' ),
	'bg_BG' => array( 'Сума', 'Начална валута', 'Целева валута', 'Размяна на валутите' ),
);
$render = function () {
	$shortcode = do_shortcode( '[exchange-rates_currency-converter from="USD" to="EUR"]' );
	ob_start();
	the_widget( '\Dejurin\ExchangeRates\Widgets\Converter', array( 'from' => 'USD', 'to' => 'EUR' ) );
	return array( 'shortcode' => $shortcode, 'widget' => ob_get_clean() );
};
$inspect = function ( $html, $expected, $case ) use ( $check ) {
	$document = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	$xpath = new DOMXPath( $document );
	$nodes = $xpath->query( '//*[@data-currencies]' );
	$check( 1 === $nodes->length, $case . ': one converter' );
	if ( ! $nodes->length ) { return; }
	foreach ( array( 'amount', 'from', 'to', 'swap' ) as $index => $key ) {
		$check( $expected[ $index ] === $nodes->item( 0 )->getAttribute( 'data-label-' . $key ), $case . ': ' . $key . ' label' );
	}
	$check( 0 === $xpath->query( '//script|//*[@onmouseover]' )->length, $case . ': labels do not inject markup' );
	$check( 1 === $xpath->query( '//*[@data-er-info and @aria-controls="exchange-rates-information"]' )->length, $case . ': shared information opener retained' );
};
try {
	foreach ( $labels as $locale => $expected ) {
		unload_textdomain( 'exchange-rates' );
		$check( load_textdomain( 'exchange-rates', WP_PLUGIN_DIR . '/exchange-rates/languages/exchange-rates-' . $locale . '.mo' ), $locale . ': catalog loads' );
		foreach ( $render() as $kind => $html ) {
			$inspect( $html, $expected, $locale . ': ' . $kind );
		}
	}
	$escaped = function ( $translation, $text, $domain ) {
		return 'exchange-rates' === $domain && in_array( $text, array( 'Amount', 'From currency', 'To currency', 'Swap currencies' ), true )
			? 'Label "quoted" <tag> & onmouseover="invalid"' : $translation;
	};
	add_filter( 'gettext', $escaped, 10, 3 );
	try {
		foreach ( $render() as $kind => $html ) {
			$inspect( $html, array_fill( 0, 4, 'Label "quoted" <tag> & onmouseover="invalid"' ), 'escaped: ' . $kind );
		}
	} finally {
		remove_filter( 'gettext', $escaped, 10 );
	}
	$scripts = wp_scripts();
	$check( array( 'jquery' ) === $scripts->registered['plugin-exchange-rates-widgets']->deps, 'Public script depends only on jQuery' );
} finally {
	unload_textdomain( 'exchange-rates' );
	remove_filter( 'pre_option_exchange-rates', $settings );
	remove_filter( 'pre_option_exchange-rates_rates', $rates );
}
foreach ( $saved as $name => $value ) {
	$check( $value === get_option( $name ), $name . ': saved state unchanged' );
}
WP_CLI::log( sprintf( 'Converter controls: %d/%d passed, %d failed.', $checks - $failures, $checks, $failures ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
