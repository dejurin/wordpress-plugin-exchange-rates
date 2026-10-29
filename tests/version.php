<?php
/** Exercise the real caption with a different version and HTML-sensitive bytes. */

namespace Dejurin\ExchangeRates {
	class Plugin {
		const VERSION     = '9.8.7-probe<&"';
		const NAME        = 'Exchange Rates';
		const PLUGIN_SLUG = 'exchange-rates';
	}
}

namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	date_default_timezone_set( 'UTC' );
	require dirname( __DIR__ ) . '/plugin/trunk/vendor/autoload.php';

	function esc_html( $value ) {
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
	function esc_attr( $value ) {
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
	function esc_url( $value ) {
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
	function esc_html__( $value, $domain ) {
		return esc_html( $value );
	}
	function esc_attr__( $value, $domain ) {
		return esc_attr( $value );
	}
	function __( $text, $domain ) { return $text; }
	function esc_html_e( $text, $domain ) { echo esc_html( $text ); }
	function esc_attr_e( $text, $domain ) { echo esc_attr( $text ); }
	function add_action() {}
	function wp_date( $format, $timestamp ) {
		return gmdate( $format, $timestamp );
	}

	$checks   = 0;
	$failures = 0;
	function check_version( $expected, $actual, $label ) {
		global $checks, $failures;
		++$checks;
		if ( $expected !== $actual ) {
			++$failures;
			fwrite( STDERR, 'FAIL: ' . $label . PHP_EOL );
		}
	}
	function version_xpath( $html ) {
		$previous = libxml_use_internal_errors( true );
		$document = new \DOMDocument();
		$document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return new \DOMXPath( $document );
	}

	foreach ( array( 0, 1, 2 ) as $mode ) {
		$html = \Dejurin\ExchangeRates\Models\Dev::caption(
			array( 'base_currency' => 'USD', 'quote_currency' => 'EUR', 'full_width' => true ),
			array( 'put_time' => '2026-01-02 12:01:00', 'local_time' => '2026-01-02 12:00:00' ),
			1, 'test-widget', 'currencyrate', $mode
		);
        check_version( true, false !== strpos( $html, 'aria-controls="exchange-rates-information"' ), 'Shared dialog target, mode ' . $mode );
    }
    ob_start(); \Dejurin\ExchangeRates\Service\Footer::render_dialog(); $html = ob_get_clean();
    $xpath = version_xpath( $html );
    $links = $xpath->query( '//a[@href="https://wordpress.org/plugins/exchange-rates/"]' );
    check_version( 1, $links->length, 'One original plugin link in shared dialog' );
    $link = $links->item( 0 );
    check_version( false, false !== strpos( $link->textContent, \Dejurin\ExchangeRates\Plugin::VERSION ), 'Version remains outside the plugin link' );
    check_version( true, false !== strpos( $html, esc_html( \Dejurin\ExchangeRates\Plugin::VERSION ) ), 'Version is HTML-escaped' );
    check_version( true, false !== strpos( $link->parentNode->textContent, \Dejurin\ExchangeRates\Plugin::VERSION . ' 🚀' ), 'Version precedes the rocket' );
    check_version( 'noreferrer noopener nofollow', $link->getAttribute( 'rel' ), 'Original dialog link attributes' );

	$html = \Dejurin\ExchangeRates\Models\Dev::rates_unavailable();
	$nodes = version_xpath( $html )->query( '//*[@data-exchange-rates-version]' );
	check_version( 1, $nodes->length, 'Unavailable output carries one version' );
	check_version( \Dejurin\ExchangeRates\Plugin::VERSION, $nodes->length ? $nodes->item( 0 )->getAttribute( 'data-exchange-rates-version' ) : null, 'Attribute escapes and preserves the version' );
	check_version( true, false !== strpos( $html, 'data-exchange-rates-version="' . esc_attr( \Dejurin\ExchangeRates\Plugin::VERSION ) . '"' ), 'Attribute uses HTML escaping' );

	printf( "%s: %d version assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
	exit( $failures ? 1 : 0 );
}
