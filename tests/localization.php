<?php
/**
 * Catalog coverage, MO loading and placeholder regressions.
 * Run with a WordPress POMO directory, or through wp eval-file.
 */
$running_wordpress = defined( 'ABSPATH' );
$pomo = $running_wordpress ? ABSPATH . 'wp-includes/pomo' : ( isset( $argv[1] ) ? $argv[1] : '' );
// Standalone POMO loading also needs the core PHP compatibility functions.
if ( is_file( dirname( $pomo ) . '/compat.php' ) ) {
	if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', dirname( dirname( $pomo ) ) . '/' ); }
	if ( ! defined( 'WPINC' ) ) { define( 'WPINC', 'wp-includes' ); }
	require_once dirname( $pomo ) . '/compat.php';
}
require_once $pomo . '/po.php';
require_once $pomo . '/mo.php';

$root = dirname( __DIR__ ) . '/plugin/trunk';
$checks = 0;
$failures = 0;
function er_catalog_check( $pass, $label ) {
	global $checks, $failures;
	++$checks;
	if ( ! $pass ) {
		++$failures;
		if ( $failures <= 30 ) { echo 'FAIL: ' . $label . "\n"; }
	}
}
function er_catalog_keys( $catalog ) {
	$keys = array_keys( $catalog->entries );
	sort( $keys );
	return $keys;
}
function er_catalog_tokens( $text, $pattern ) {
	preg_match_all( $pattern, $text, $matches );
	sort( $matches[0] );
	return $matches[0];
}
$pot = new PO();
er_catalog_check( $pot->import_from_file( $root . '/languages/exchange-rates.pot' ), 'POT loads' );
preg_match( '/^Version:\s*(.+)$/m', file_get_contents( $root . '/Install.php' ), $version );
preg_match( '/^Description:\s*(.+)$/m', file_get_contents( $root . '/Install.php' ), $description );
er_catalog_check( 'Exchange Rates ' . trim( $version[1] ) === $pot->get_header( 'Project-Id-Version' ), 'POT version matches plugin' );
er_catalog_check( isset( $pot->entries[ trim( $description[1] ) ] ), 'Current plugin description is extracted' );
$fresh_path = $running_wordpress ? ( isset( $args[0] ) ? $args[0] : '' ) : ( isset( $argv[2] ) ? $argv[2] : '' );
$fresh = new PO();
er_catalog_check( '' !== $fresh_path && $fresh->import_from_file( $fresh_path ), 'Fresh WP-CLI extraction loads' );
er_catalog_check( er_catalog_keys( $fresh ) === er_catalog_keys( $pot ), 'POT keys match current source extraction' );
$required = array(
	'No change.', 'Previous rate unavailable.', 'Increase: %s', 'Decrease: %s',
	'Change is below display precision.', 'Invalid source directory response.',
	'Showing the last valid source directory.',
);
foreach ( $required as $message ) {
	er_catalog_check( isset( $pot->entries[ $message ] ), 'POT includes ' . $message );
}
$expected = array(
	'en_US' => array( 'Increase: %s' => 'Increase: %s', 'Decrease: %s' => 'Decrease: %s' ),
	'uk' => array( 'Increase: %s' => 'Зростання: %s', 'Decrease: %s' => 'Зниження: %s', 'Formatting' => 'Форматування' ),
	'bg_BG' => array( 'Increase: %s' => 'Повишение: %s', 'Decrease: %s' => 'Понижение: %s', 'Force Data Update' => 'Принудително обновяване на данните' ),
);
foreach ( $expected as $locale => $samples ) {
	$path = $root . '/languages/exchange-rates-' . $locale;
	$po = new PO();
	$mo = new MO();
	er_catalog_check( $po->import_from_file( $path . '.po' ), $locale . ': PO loads' );
	er_catalog_check( $mo->import_from_file( $path . '.mo' ), $locale . ': MO loads in WordPress' );
	er_catalog_check( er_catalog_keys( $pot ) === er_catalog_keys( $po ), $locale . ': POT/PO keys match' );
	er_catalog_check( er_catalog_keys( $po ) === er_catalog_keys( $mo ), $locale . ': PO/MO keys match' );
	er_catalog_check( $locale === $po->get_header( 'Language' ), $locale . ': locale header' );
	er_catalog_check( $pot->get_header( 'Project-Id-Version' ) === $po->get_header( 'Project-Id-Version' ), $locale . ': version header' );
	er_catalog_check( $pot->get_header( 'POT-Creation-Date' ) === $po->get_header( 'POT-Creation-Date' ), $locale . ': extraction date' );
	er_catalog_check( 'Install.php' === $po->get_header( 'X-Poedit-WPHeader' ), $locale . ': entry-point case' );
	er_catalog_check( $po->get_header( 'Plural-Forms' ) === $mo->get_header( 'Plural-Forms' ), $locale . ': plural header retained in MO' );
	foreach ( $po->entries as $key => $entry ) {
		er_catalog_check( ! in_array( 'fuzzy', $entry->flags, true ), $locale . ': no fuzzy translation: ' . $key );
		er_catalog_check( ! empty( $entry->translations[0] ), $locale . ': translation present: ' . $key );
		er_catalog_check( isset( $mo->entries[ $key ] ) && $entry->translations === $mo->entries[ $key ]->translations, $locale . ': compiled translation matches: ' . $key );
		foreach ( $entry->translations as $text ) {
			er_catalog_check( er_catalog_tokens( $entry->singular, '/%(?:\d+\$)?[sd]/' ) === er_catalog_tokens( $text, '/%(?:\d+\$)?[sd]/' ), $locale . ': placeholders: ' . $key );
			er_catalog_check( er_catalog_tokens( $entry->singular, '/<[^>]+>/' ) === er_catalog_tokens( $text, '/<[^>]+>/' ), $locale . ': HTML tags and attributes: ' . $key );
		}
	}
	foreach ( $samples as $message => $translation ) {
		er_catalog_check( $translation === $mo->translate( $message ), $locale . ': expected UI translation: ' . $message );
		if ( false !== strpos( $message, '%s' ) ) {
			er_catalog_check( str_replace( '%s', '4%', $translation ) === sprintf( $mo->translate( $message ), '4%' ), $locale . ': formatted trend label' );
		}
	}
	// In CI this also exercises the running WordPress translation API, without changing site locale/options.
	if ( function_exists( 'load_textdomain' ) ) {
		$domain = 'exchange-rates-catalog-test';
		unload_textdomain( $domain );
		er_catalog_check( load_textdomain( $domain, $path . '.mo' ), $locale . ': load_textdomain' );
		foreach ( $samples as $message => $translation ) {
			er_catalog_check( $translation === __( $message, $domain ), $locale . ': WordPress gettext API: ' . $message );
		}
		unload_textdomain( $domain );
	}
}
printf( "%s: %d localization assertions, %d failures, PHP %s\n", $failures ? 'FAIL' : 'PASS', $checks, $failures, PHP_VERSION );
if ( $failures ) { exit( 1 ); }
