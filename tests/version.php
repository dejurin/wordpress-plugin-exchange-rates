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
		$xpath = version_xpath( $html );
		$links = $xpath->query( '//a[@href="https://wordpress.org/plugins/exchange-rates/"]' );
		check_version( 1, $links->length, 'One original plugin link, mode ' . $mode );
		$link = $links->item( 0 );
		check_version( 'Exchange Rates', $link->textContent, 'Version is outside the link, mode ' . $mode );
		check_version( ' ' . \Dejurin\ExchangeRates\Plugin::VERSION, $link->nextSibling ? $link->nextSibling->nodeValue : null, 'Dynamic plain-text version, mode ' . $mode );
		check_version( true, false !== strpos( $html, '</a> ' . esc_html( \Dejurin\ExchangeRates\Plugin::VERSION ) . '</small>' ), 'Version is HTML-escaped, mode ' . $mode );
		check_version( true, false !== strpos( $link->parentNode->parentNode->textContent, \Dejurin\ExchangeRates\Plugin::VERSION . ' 🚀' ), 'Version precedes the rocket, mode ' . $mode );
		check_version( 'noreferrer noopener nofollow', $link->getAttribute( 'rel' ), 'Original link attributes, mode ' . $mode );
	}
	$html = \Dejurin\ExchangeRates\Models\Dev::rates_unavailable();
	$nodes = version_xpath( $html )->query( '//*[@data-exchange-rates-version]' );
	check_version( 1, $nodes->length, 'Unavailable output carries one version' );
	check_version( \Dejurin\ExchangeRates\Plugin::VERSION, $nodes->length ? $nodes->item( 0 )->getAttribute( 'data-exchange-rates-version' ) : null, 'Attribute escapes and preserves the version' );
	check_version( true, false !== strpos( $html, 'data-exchange-rates-version="' . esc_attr( \Dejurin\ExchangeRates\Plugin::VERSION ) . '"' ), 'Attribute uses HTML escaping' );

	printf( "%s: %d version assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
	exit( $failures ? 1 : 0 );
}
