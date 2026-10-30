<?php
/** Standalone rendering contracts for the installable footer. */
define( 'ABSPATH', __DIR__ );
function __( $text, $domain ) { return $text; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return preg_match( '#^https://#', $value ) ? esc_attr( $value ) : ''; }
function esc_html__( $text, $domain ) { return esc_html( $text ); }
function esc_attr__( $text, $domain ) { return esc_attr( $text ); }
function esc_html_e( $text, $domain ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain ) { echo esc_attr( $text ); }
function add_action() {}
require __DIR__ . '/../plugin/trunk/src/Plugin.php';
require __DIR__ . '/../plugin/trunk/src/Models/Sources.php';
$file = __DIR__ . '/../plugin/trunk/src/Service/Footer.php';
if ( ! is_file( $file ) ) { fwrite( STDERR, "FAIL: Installable footer is missing.\n" ); exit( 1 ); }
require $file;
use Dejurin\ExchangeRates\Service\Footer;
use Dejurin\ExchangeRates\Models\Sources;
$checks = 0;
$failures = 0;
function check_footer( $condition, $message ) {
	global $checks, $failures;
	++$checks;
	if ( ! $condition ) { ++$failures; echo 'FAIL: ' . $message . "\n"; }
}
function footer_xpath( $html ) {
	$previous = libxml_use_internal_errors( true );
	$document = new DOMDocument();
	$document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	return new DOMXPath( $document );
}
$parameters = array( 'base_currency' => 'USD', 'quote_currency' => 'EUR' );
$time = array( 'local_time' => '2026-10-27 11:50:00', 'put_time' => '2026-10-27 11:51:00' );
foreach ( Sources::get_list() as $id => $source ) {
	check_footer( ! empty( $source['compact'] ), $id . ': compact label exists' );
	foreach ( array( 0, 1, 2 ) as $kind ) {
		$html = Footer::caption( $parameters, $time, $id, $kind );
		check_footer( false !== strpos( $html, 'data-er-info' ), $id . ': modal opener' );
		check_footer( false !== strpos( $html, esc_attr( $source['name'] ) ), $id . ': actual source identity' );
		check_footer( false !== strpos( $html, '2026-10-27T11:50:00Z' ), $id . ': rate date UTC' );
		check_footer( false !== strpos( $html, 'data-recorded-at="2026-10-27T11:51:00Z"' ), $id . ': saved date UTC' );
		check_footer( false === strpos( $html, 'nofollow' ), $id . ': visible attribution links' );
		check_footer( false === strpos( $html, '<dialog' ), $id . ': no repeated inline dialog' );
		$xpath = footer_xpath( $html );
		$button = $xpath->query( '//button[@data-er-info]' )->item( 0 );
		$caption_time = $xpath->query( '//button[@data-er-info]//time' )->item( 0 );
		check_footer( $caption_time && '27 Oct' === $caption_time->textContent, $id . '/' . $kind . ': no-script caption shows day and month only' );
		check_footer( $button && false !== strpos( $button->getAttribute( 'title' ), $source['name'] ) && false !== strpos( $button->getAttribute( 'title' ), '27 Oct 2026 11:50 UTC' ), $id . '/' . $kind . ': title preserves full source and UTC date' );
		check_footer( $button && $button->getAttribute( 'title' ) === $button->getAttribute( 'aria-label' ), $id . '/' . $kind . ': accessible name preserves title details' );
		check_footer( ( 2 === $kind ? 2 : 1 ) === $xpath->query( '//a[@href]' )->length, $id . '/' . $kind . ': visible attribution link count is unchanged' );
		$expected_links = 2 === $kind ? array(
			array( 'https://currencyrate.today/', 'CurrencyRate (opens in a new tab)', '_blank', 'noreferrer noopener' ),
			array( 'https://currencyconvert.online/usd', 'USD (opens in a new tab)', '_blank', 'noreferrer noopener' ),
		) : array( array( 1 === $kind ? 'https://usd.currencyrate.today/eur' : 'https://currencyconvert.online/exchange-rates', ( 1 === $kind ? 'CurrencyRate' : 'FX Source' ) . ' (opens in a new tab)', '_blank', 'noreferrer noopener' ) );
		$actual_links = array();
		foreach ( $xpath->query( '//a[@href]' ) as $link ) {
			$actual_links[] = array( $link->getAttribute( 'href' ), trim( $link->textContent ), $link->getAttribute( 'target' ), $link->getAttribute( 'rel' ) );
		}
		check_footer( $expected_links === $actual_links, $id . '/' . $kind . ': visible attribution URLs, anchors, target and rel are unchanged' );
	}
}
check_footer( 'BCB' === Sources::get_list()['br-bcb']['compact'], 'BCB abbreviation excludes quote currency' );
$table = Footer::caption( $parameters, $time, 'eu-ecb', 0 );
check_footer( false !== strpos( $table, 'href="https://currencyconvert.online/exchange-rates"' ), 'USD table landing page' );
$parameters['base_currency'] = 'EUR';
check_footer( false !== strpos( Footer::caption( $parameters, $time, 'eu-ecb', 0 ), 'href="https://currencyconvert.online/exchange-rates/eur"' ), 'EUR table landing page' );
$parameters['base_currency'] = 'USD';
check_footer( false !== strpos( Footer::caption( $parameters, $time, 'currencyrate', 1 ), 'href="https://usd.currencyrate.today/eur"' ), 'Converter pair landing page' );
check_footer( false !== strpos( Footer::caption( $parameters, $time, 'currencyrate', 2 ), 'href="https://currencyconvert.online/usd"' ), 'Badge base landing page' );
$invalid = Footer::caption( $parameters, array( 'local_time' => 'not a date' ), 'missing-source', 0 );
check_footer( false === strpos( $invalid, 'datetime=' ), 'Invalid time never becomes a fabricated date' );
check_footer( false !== strpos( $invalid, 'Unknown source' ), 'Unknown source is explicit' );
$invalid_xpath = footer_xpath( $invalid );
check_footer( 'Not available' === trim( $invalid_xpath->query( '//button[@data-er-info]' )->item( 0 )->textContent ), 'Invalid source date remains explicitly unavailable' );
$offset = Footer::caption( $parameters, array( 'local_time' => '2026-01-01T00:15:00+02:00', 'put_time' => '2025-12-31T22:16:00Z' ), 'eu-ecb', 2 );
$offset_xpath = footer_xpath( $offset );
$offset_time = $offset_xpath->query( '//button[@data-er-info]//time' )->item( 0 );
check_footer( '2025-12-31T22:15:00Z' === $offset_time->getAttribute( 'datetime' ), 'Offset source instant normalizes to UTC across year boundary' );
check_footer( '31 Dec' === $offset_time->textContent, 'No-script caption uses normalized UTC day and month' );
check_footer( false !== strpos( $offset_xpath->query( '//button[@data-er-info]' )->item( 0 )->getAttribute( 'title' ), '31 Dec 2025 22:15 UTC' ), 'No-script title retains normalized full date at year boundary' );
ob_start(); Footer::render_dialog(); $modal = ob_get_clean();
check_footer( false !== strpos( $modal, 'hidden style="display:none!important"' ), 'Dialog has no-script closed guard' );
$modal_xpath = footer_xpath( $modal );
$disclaimer = $modal_xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " er-dialog-disclaimer ")]//p' )->item( 0 );
$heading_marker = $modal_xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " er-dialog-head ")]//*[contains(concat(" ", normalize-space(@class), " "), " er-dialog-heading ")]//*[contains(concat(" ", normalize-space(@class), " "), " er-dialog-search-marker ")]' )->item( 0 );
check_footer( $disclaimer && 'Exchange rates are provided for reference only and may be delayed. Displayed rates may differ from those available for actual transactions. Confirm the final rate and applicable fees with your bank, exchange service or payment provider before making a transaction.' === $disclaimer->textContent, 'Reference disclaimer has the exact approved wording' );
check_footer( 1 === substr_count( $modal, '[WP_ExchangeRates]' ), 'Search marker occurs exactly once' );
check_footer( false === strpos( $modal, '[ExchangeRatesWP]' ), 'Previous search marker is absent from current footer output' );
check_footer( $heading_marker && '[WP_ExchangeRates]' === $heading_marker->textContent, 'Search marker belongs to dialog heading group' );
check_footer( 1 === $modal_xpath->query( '//h2[@id="exchange-rates-information-title"]/following-sibling::*[contains(concat(" ", normalize-space(@class), " "), " er-dialog-search-marker ")]' )->length, 'Search marker follows the modal title as a separate element' );
check_footer( 0 === $modal_xpath->query( '//h2[contains(., "[WP_ExchangeRates]")] | //footer[contains(., "[WP_ExchangeRates]")]' )->length, 'Search marker stays outside title text and footer identity' );
check_footer( $disclaimer && false === strpos( $disclaimer->textContent, '[WP_ExchangeRates]' ), 'Search marker stays outside the disclaimer' );
check_footer( 0 === $modal_xpath->query( '//a[contains(., "[WP_ExchangeRates]")]' )->length, 'Search marker does not change any backlink anchor' );
check_footer( 3 === $modal_xpath->query( '//a' )->length && 2 === $modal_xpath->query( '//a[@href]' )->length, 'Shared dialog preserves link count and initially inert source link' );
check_footer( 1 === $modal_xpath->query( '//a[@href="https://wordpress.org/plugins/exchange-rates/" and @target="_blank" and @rel="noreferrer noopener nofollow"]' )->length, 'Shared plugin backlink attributes are unchanged' );
check_footer( 1 === $modal_xpath->query( '//a[@href="https://currencyrate.today/" and @target="_blank" and @rel="noreferrer noopener nofollow"]' )->length, 'Shared API backlink attributes are unchanged' );
check_footer( false !== strpos( $modal, '1.5.0' ), 'Plugin version comes from runtime' );
check_footer( 1 === preg_match( '/<dt[^>]*>Check:<\/dt><dd><time data-er-modal-recorded>/', $modal ), 'Published Check label retains the saved-entry timestamp' );
check_footer( 1 === preg_match( '/<dt[^>]*>Latest change:<\/dt><dd><time data-er-modal-date>/', $modal ), 'Published Latest change label retains the source timestamp' );
check_footer( strpos( $modal, '>Check:</dt>' ) < strpos( $modal, '>Latest change:</dt>' ), 'Published date-label order is retained' );
preg_match_all( '/<a\b[^>]+>/', $modal, $links );
foreach ( $links[0] as $link ) { check_footer( false !== strpos( $link, 'nofollow' ), 'Dialog link nofollow' ); }
ob_start(); Footer::render_dialog(); $duplicate = ob_get_clean();
check_footer( '' === $duplicate, 'Only one dialog per page' );
echo sprintf( "Footer rendering: %d/%d passed, %d failed.\n", $checks - $failures, $checks, $failures );
exit( $failures ? 1 : 0 );
