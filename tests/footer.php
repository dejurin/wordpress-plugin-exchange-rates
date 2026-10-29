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
ob_start(); Footer::render_dialog(); $modal = ob_get_clean();
check_footer( false !== strpos( $modal, 'hidden style="display:none!important"' ), 'Dialog has no-script closed guard' );
check_footer( false !== strpos( $modal, '[ExchangeRatesWP]' ), 'Search marker retained' );
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
