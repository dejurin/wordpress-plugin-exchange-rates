<?php
/** Local emoji assets must remain complete, inert, and compatible with existing renderers. */
define( 'ABSPATH', __DIR__ );
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_html__( $value, $domain ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_attr( $value ); }
function plugin_dir_url( $file ) { return 'https://example.test/wp-content/plugins/exchange-rates/'; }
require __DIR__ . '/../plugin/trunk/vendor/autoload.php';

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Emoji;
use Dejurin\ExchangeRates\Service\CurrencyTable;

class EmojiFlagsTestBadge extends \Dejurin\ExchangeRates\Shortcodes\Badge {
	public function __construct() {}
	public function image( $type, $currency ) { return $this->img( $type, $currency ); }
}

$checks = 0;
$failures = 0;
function check_emoji( $condition, $message ) {
	global $checks, $failures;
	++$checks;
	if ( ! $condition ) {
		++$failures;
		if ( $failures <= 20 ) { echo 'FAIL: ' . $message . "\n"; }
	}
}

function check_emoji_image( $html, $src, $width, $height, $alt, $context ) {
	$document = new DOMDocument();
	$document->loadHTML( '<!doctype html><html><body>' . $html . '</body></html>' );
	$images = $document->getElementsByTagName( 'img' );
	check_emoji( 1 === $images->length, $context . ': exactly one image' );
	if ( 1 !== $images->length ) { return; }
	$image = $images->item( 0 );
	check_emoji( $src === $image->getAttribute( 'src' ), $context . ': plugin-local source' );
	check_emoji( 'lazy' === $image->getAttribute( 'loading' ), $context . ': loading preserved' );
	check_emoji( $alt === $image->getAttribute( 'alt' ), $context . ': alternative text preserved' );
	check_emoji( ( 'width:' . $width . 'px!important;height:' . $height . 'px!important' ) === $image->getAttribute( 'style' ), $context . ': dimensions preserved' );
}

$emoji = Emoji::get_list();
$asset_root = __DIR__ . '/../plugin/trunk/assets/flags/emoji/';
foreach ( array_unique( array_values( $emoji ) ) as $code ) {
	check_emoji( 1 === preg_match( '/^[0-9A-F]+(?:-[0-9A-F]+)*$/', $code ), $code . ': safe mapped filename' );
	$file = $asset_root . strtolower( $code ) . '.svg';
	check_emoji( is_file( $file ), $code . ': bundled SVG exists' );
	if ( ! is_file( $file ) ) { continue; }
	$svg = file_get_contents( $file );
	check_emoji( false === stripos( $svg, '<!DOCTYPE' ) && false === stripos( $svg, '<!ENTITY' ), $code . ': no XML entities or external DTD' );
	$document = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$loaded = $document->loadXML( $svg, LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	check_emoji( $loaded && 'svg' === $document->documentElement->localName && 'http://www.w3.org/2000/svg' === $document->documentElement->namespaceURI, $code . ': valid SVG document' );
	if ( ! $loaded ) { continue; }
	$safe = true;
	foreach ( $document->getElementsByTagName( '*' ) as $node ) {
		// Artwork is static geometry. Reject executable, embedded and animated content.
		if ( 'http://www.w3.org/2000/svg' !== $node->namespaceURI ) { $safe = false; }
		if ( ! in_array( $node->localName, array( 'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'defs', 'clipPath', 'mask', 'title', 'desc' ), true ) ) { $safe = false; }
		foreach ( $node->attributes as $attribute ) {
			if ( 0 === stripos( $attribute->localName, 'on' ) ) { $safe = false; }
			if ( in_array( strtolower( $attribute->localName ), array( 'href', 'src' ), true ) && '#' !== substr( trim( $attribute->value ), 0, 1 ) ) { $safe = false; }
			preg_match_all( '/url\(([^)]*)\)/i', $attribute->value, $references );
			foreach ( $references[1] as $reference ) {
				if ( '#' !== substr( trim( $reference, " \t\r\n\"'" ), 0, 1 ) ) { $safe = false; }
			}
		}
	}
	check_emoji( $safe, $code . ': static geometry without scripts, events or external references' );
}

// These published mappings deliberately use banknotes, not national flag artwork.
foreach ( array( 'EU' => '1F4B6', 'GB' => '1F4B7', 'US' => '1F4B5', 'JP' => '1F4B4' ) as $flag => $code ) {
	check_emoji( isset( $emoji[ $flag ] ) && $code === $emoji[ $flag ], $flag . ': published banknote mapping preserved' );
}
$GLOBALS['dejurin_exchange_rates'] = (object) array( 'plugin_path' => __DIR__ . '/../plugin/trunk/Install.php' );
$base_url = plugin_dir_url( $GLOBALS['dejurin_exchange_rates']->plugin_path );
$table = new CurrencyTable();
$table->parameters = array( 'flag_type' => 'emoji', 'flag_size' => 16, 'region' => true, 'full_width' => true );
$label = new ReflectionMethod( $table, 'render_label' );
if ( PHP_VERSION_ID < 80100 ) { $label->setAccessible( true ); }
$badge = new EmojiFlagsTestBadge();
foreach ( Currencies::get_list() as $currency_code => $currency ) {
	$flag = $currency['flag'];
	check_emoji( isset( $emoji[ $flag ] ), $currency_code . ': currency flag has an emoji mapping' );
	if ( ! isset( $emoji[ $flag ] ) ) { continue; }
	$src = $base_url . 'assets/flags/emoji/' . strtolower( $emoji[ $flag ] ) . '.svg';
	check_emoji_image( $label->invoke( $table, $currency_code, $currency['region'], $flag ), $src, 16, 16, $currency_code, $currency_code . ' table' );
	check_emoji_image( $badge->image( 'emoji', $currency ), $src, 20, 20, $flag, $currency_code . ' badge' );
}

// Adjacent saved flag modes and boundary sizes must not change with asset hosting.
foreach ( array( 'rectangular', 'square', 'circular', 'emoji', 'none' ) as $type ) {
	$table->parameters['flag_type'] = $type;
	$src = $base_url . 'assets/flags/' . $type . '/' . ( 'emoji' === $type ? '1f4b5' : 'us' ) . '.svg';
	foreach ( array( 16, 39, 40, 64 ) as $size ) {
		$table->parameters['flag_size'] = $size;
		$html = $label->invoke( $table, 'USD & dollar', 'United States', 'US' );
		if ( 'none' === $type ) { check_emoji( false === strpos( $html, '<img' ), 'none: no table image' ); }
		else { check_emoji_image( $html, $src, $size, 'rectangular' === $type ? $size * 0.75 : $size, 'USD & dollar', $type . ' table ' . $size ); }
	}
	$html = $badge->image( $type, array( 'flag' => 'US' ) );
	if ( 'none' === $type ) { check_emoji( '' === $html, 'none: no badge image' ); }
	else { check_emoji_image( $html, $src, 20, 'rectangular' === $type ? 15 : 20, 'US', $type . ' badge' ); }
}
check_emoji( '' === $badge->image( 'emoji', array( 'flag' => 'UNKNOWN' ) ), 'Unknown badge flag stays empty' );
echo sprintf( "Emoji flags: %d/%d passed, %d failed; %d mapped assets checked.\n", $checks - $failures, $checks, $failures, count( array_unique( array_values( $emoji ) ) ) );
exit( $failures ? 1 : 0 );
