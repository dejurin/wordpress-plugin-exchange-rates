<?php
/** Verify version metadata through real shortcodes and sanitized native widgets. */

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Version checks require the isolated local test environment.' );
}

set_error_handler( function ( $severity, $message, $file, $line ) {
	if ( error_reporting() & $severity ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR );

global $version_checks, $version_failures;
$version_checks   = 0;
$version_failures = 0;
function check_rendered_version( $expected, $actual, $label ) {
	global $version_checks, $version_failures;
	++$version_checks;
	if ( $expected !== $actual ) {
		++$version_failures;
		if ( $version_failures <= 12 ) {
			WP_CLI::warning( $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
		}
	}
}
function rendered_version_xpath( $html ) {
	$previous = libxml_use_internal_errors( true );
	$document = new DOMDocument();
	$document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	return new DOMXPath( $document );
}
function render_version_cases( $currency = 'EUR' ) {
	$cases = array(
		'shortcode-table'     => do_shortcode( '[exchange-rates_currency-table base_currency="USD" currency_list="' . $currency . '" flag_type="none" code="true" version="spoofed"]' ),
		'shortcode-converter' => do_shortcode( '[exchange-rates_currency-converter from="USD" to="EUR" version="spoofed"]' ),
		'badge'              => do_shortcode( '[exchange-rates_badge base_currency="USD" currency_list="' . $currency . '" flag_type="none" version="spoofed"]' ),
	);
	foreach ( array( 'Table', 'Converter' ) as $type ) {
		$instance = array( 'base_currency' => 'USD', 'currency_list' => array( $currency ), 'flag_type' => 'none', 'code' => true, 'from' => 'USD', 'to' => 'EUR', 'version' => 'spoofed' );
		ob_start();
		the_widget( '\\Dejurin\\ExchangeRates\\Widgets\\' . $type, $instance, array( 'before_widget' => '<div>', 'after_widget' => '</div>' ) );
		$cases[ 'widget-' . strtolower( $type ) ] = ob_get_clean();
	}
	return $cases;
}

$saved = array();
foreach ( array( Settings::$option_name, 'exchange-rates_rates', 'widget_exchange-rates_currency-table', 'widget_exchange-rates_currency-converter' ) as $name ) {
	$saved[ $name ] = get_option( $name );
}
$settings = function () {
	return array_merge( Settings::get_defaults(), array( 'rates_available' => true, 'update_timestamp' => time() ) );
};
$current = array( 'USD' => 1, 'EUR' => 1.25 );
$rates = function () use ( &$current ) {
	return array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
		array( 'put_time' => '2026-01-02 12:01:00', 'local_time' => '2026-01-02 12:00:00', 'rates' => $current ),
		array( 'put_time' => '2026-01-01 12:01:00', 'local_time' => '2026-01-01 12:00:00', 'rates' => $current ),
	) );
};
add_filter( 'pre_option_' . Settings::$option_name, $settings );
add_filter( 'pre_option_exchange-rates_rates', $rates );
try {
	$normal = render_version_cases();
	foreach ( $normal as $name => $html ) {
		$xpath = rendered_version_xpath( $html );
		$nodes = $xpath->query( '//*[@data-exchange-rates-version]' );
		check_rendered_version( 1, $nodes->length, $name . ': one component version' );
		check_rendered_version( Plugin::VERSION, $nodes->length ? $nodes->item( 0 )->getAttribute( 'data-exchange-rates-version' ) : null, $name . ': server version survives output sanitization' );
		$links = $xpath->query( '//a[@href="https://wordpress.org/plugins/exchange-rates/"]' );
		check_rendered_version( 1, $links->length, $name . ': original plugin link' );
		$link = $links->item( 0 );
		check_rendered_version( 'Exchange Rates', $link->textContent, $name . ': version is not part of the link' );
		check_rendered_version( ' ' . Plugin::VERSION, $link->nextSibling ? $link->nextSibling->nodeValue : null, $name . ': plain version after link' );
		check_rendered_version( true, false !== strpos( $link->parentNode->parentNode->textContent, Plugin::VERSION . ' 🚀' ), $name . ': version before rocket' );
		check_rendered_version( '_blank', $link->getAttribute( 'target' ), $name . ': existing link target' );
		check_rendered_version( 'noreferrer noopener nofollow', $link->getAttribute( 'rel' ), $name . ': existing link rel' );
		check_rendered_version( true, false !== strpos( $html, esc_html__( 'This plugin or website cannot guarantee the accuracy of the exchange rates displayed. You should confirm current rates before making any transactions that could be affected by changes in the exchange rates.', 'exchange-rates' ) ), $name . ': original disclaimer' );
		check_rendered_version( true, false !== strpos( $link->parentNode->textContent, __( 'You can install this WP plugin on your website from the WordPress official website:', 'exchange-rates' ) ), $name . ': original translated credit' );
	}
	$xpath = rendered_version_xpath( implode( '', $normal ) );
	check_rendered_version( 5, $xpath->query( '//*[@data-exchange-rates-version]' )->length, 'Multiple components keep one version each' );

	$current = array();
	foreach ( render_version_cases() as $name => $html ) {
		$xpath = rendered_version_xpath( $html );
		$nodes = $xpath->query( '//*[@data-exchange-rates-version]' );
		check_rendered_version( 1, $nodes->length, $name . ': unavailable output has a version' );
		check_rendered_version( Plugin::VERSION, $nodes->length ? $nodes->item( 0 )->getAttribute( 'data-exchange-rates-version' ) : null, $name . ': unavailable version value' );
		check_rendered_version( true, false !== strpos( $html, esc_html__( 'Error: exchange-rate data is not available yet.', 'exchange-rates' ) ), $name . ': unavailable message unchanged' );
	}
	$current = array( 'USD' => 1, 'EUR' => 1.25 );
	foreach ( render_version_cases( 'CHF' ) as $name => $html ) {
		if ( false !== strpos( $name, 'converter' ) ) {
			continue;
		}
		$xpath = rendered_version_xpath( $html );
		$nodes = $xpath->query( '//*[@data-exchange-rates-version]' );
		check_rendered_version( 1, $nodes->length, $name . ': invalid-pair output has a version' );
		check_rendered_version( Plugin::VERSION, $nodes->length ? $nodes->item( 0 )->getAttribute( 'data-exchange-rates-version' ) : null, $name . ': invalid-pair version value' );
		check_rendered_version( true, false !== strpos( $html, esc_html__( 'Error: Check parameters of widget or shortcode. Probably you changed the source of currency rates, where there was not the desired currency that you selected before.', 'exchange-rates' ) ), $name . ': invalid-pair message unchanged' );
	}
} finally {
	remove_filter( 'pre_option_' . Settings::$option_name, $settings );
	remove_filter( 'pre_option_exchange-rates_rates', $rates );
	restore_error_handler();
}
foreach ( $saved as $name => $value ) {
	check_rendered_version( $value, get_option( $name ), $name . ': stored value unchanged' );
}
if ( $version_failures ) {
	WP_CLI::error( $version_failures . ' of ' . $version_checks . ' version assertions failed.' );
}
WP_CLI::success( $version_checks . ' version assertions passed; saved settings, rates and widgets unchanged.' );
