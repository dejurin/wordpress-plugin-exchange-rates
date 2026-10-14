<?php
/** Seed or verify saved formatting fixtures in disposable WordPress only. */

use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Service\RatesData;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Formatting checks require the isolated local test environment.' );
}

set_error_handler( function ( $severity, $message, $file, $line ) {
	if ( error_reporting() & $severity ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR );

$mode = isset( $args[0] ) ? $args[0] : '';
if ( ! in_array( $mode, array( 'seed', 'verify' ), true ) ) {
	WP_CLI::error( 'Usage: wp eval-file /opt/tests/formatting-wordpress.php seed|verify' );
}

if ( 'seed' === $mode ) {
	$settings                    = get_option( Settings::$option_name );
	$settings['decimals']         = 0;
	$settings['currency_format']  = 3;
	$settings['update_timestamp'] = time();
	update_option( Settings::$option_name, $settings );
	$rates = RatesData::get();
	foreach ( array( 0, 1 ) as $index ) {
		$rates['data'][ $index ]['rates']['EUR'] = 1;
	}
	update_option( 'exchange-rates_rates', $rates );
	$instance = array( 'title' => 'Saved formatting widget', 'amount' => '1000', 'currency_list' => array( 'EUR' ), 'base_currency' => 'USD', 'flag_type' => 'none', 'decimals' => 0 );
	update_option( 'widget_exchange-rates_currency-table', array( 2 => $instance, '_multiwidget' => 1 ) );
	$content = '<p>Local formatting fixture. Synthetic rates only.</p>'
		. '[exchange-rates_currency-table id="formatting-table" amount="1000" currency_list="EUR" flag_type="none" code="true" amount_active="true" table_headers_previous_close_show="true"]'
		. '[exchange-rates_badge id="formatting-badge" amount="100" currency_list="EUR" flag_type="none" code="true" decimals="0"]';
	$page = get_page_by_path( 'exchange-rates-formatting' );
	$id   = wp_insert_post( array(
		'ID'           => $page ? $page->ID : 0,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => 'exchange-rates-formatting',
		'post_title'   => 'Formatting regression - synthetic data',
		'post_content' => $content,
	), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	update_option( 'exchange_rates_formatting_snapshot', array(
		'settings' => get_option( Settings::$option_name ),
		'rates'    => get_option( 'exchange-rates_rates' ),
		'widgets'  => get_option( 'widget_exchange-rates_currency-table' ),
		'content'  => get_post( $id )->post_content,
		'page_id'  => $id,
	) );
	WP_CLI::success( 'Saved settings, widget and shortcode fixture seeded. Page: ' . get_permalink( $id ) );
	return;
}

$snapshot = get_option( 'exchange_rates_formatting_snapshot' );
if ( ! is_array( $snapshot ) ) {
	WP_CLI::error( 'Seed the fixture before verification.' );
}
// WP-CLI evaluates this file inside a method, so explicitly share the counters.
global $formatting_checks, $formatting_failures;
$formatting_checks   = 0;
$formatting_failures = 0;
function check_saved_formatting( $expected, $actual, $label ) {
	global $formatting_checks, $formatting_failures;
	++$formatting_checks;
	if ( $expected !== $actual ) {
		++$formatting_failures;
		if ( $formatting_failures <= 12 ) {
			WP_CLI::warning( $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
		}
	}
}

function formatting_html_values( $html, $query ) {
	$previous = libxml_use_internal_errors( true );
	$document = new DOMDocument();
	$document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	$xpath  = new DOMXPath( $document );
	$values = array();
	foreach ( $xpath->query( $query ) as $node ) {
		$values[] = trim( $node->textContent );
	}
	return $values;
}

$expected_thousands = array( '1.000', '1 000', '1 000', '1,000', "1'000", "1'000", '1000', '1000' );
foreach ( $expected_thousands as $format_id => $expected ) {
	foreach ( array( 0, 4 ) as $decimals ) {
		// Exercise existing settings without rewriting them or invoking the update lifecycle.
		$filter = function ( $settings ) use ( $format_id, $decimals ) {
			$settings['currency_format'] = $format_id;
			$settings['decimals']        = $decimals;
			return $settings;
		};
		add_filter( 'option_exchange-rates', $filter );
		$html = do_shortcode( $snapshot['content'] );
		check_saved_formatting( array( $expected, $expected ), formatting_html_values( $html, '//td[@data-rate="1"]' ), 'Saved shortcode current/previous rate, format ' . $format_id . ', precision ' . $decimals );
		check_saved_formatting( array( $expected ), formatting_html_values( $html, '//input/@value' ), 'Saved amount' );
		check_saved_formatting( array( '100' ), formatting_html_values( $html, '//div[@class="badge-leaders"]/span[2]' ), 'Saved badge with explicit zero precision' );
		ob_start();
		the_widget( '\Dejurin\ExchangeRates\Widgets\Table', $snapshot['widgets'][2], array( 'before_widget' => '<section>', 'after_widget' => '</section>' ) );
		$widget_html = ob_get_clean();
		check_saved_formatting( array( $expected ), formatting_html_values( $widget_html, '//td[@data-rate="1"]' ), 'Saved widget' );
		$currency = new Currency( array( 'base_currency' => 'USD', 'amount' => 0, 'inverse' => false, 'reverse' => true ), 'EUR' );
		check_saved_formatting( '&ndash;', $currency->get_rate_format(), 'Existing zero-rate placeholder' );
		check_saved_formatting( '&ndash;', $currency->get_change_format(), 'Existing zero-change placeholder' );
		check_saved_formatting( null, $currency->get_change_format( true ), 'Existing raw zero-change sentinel' );
		remove_filter( 'option_exchange-rates', $filter );
	}
}

check_saved_formatting( $snapshot['settings'], get_option( Settings::$option_name ), 'Settings preserved' );
check_saved_formatting( $snapshot['rates'], get_option( 'exchange-rates_rates' ), 'Rates and provenance preserved' );
check_saved_formatting( $snapshot['widgets'], get_option( 'widget_exchange-rates_currency-table' ), 'Widget options preserved' );
check_saved_formatting( $snapshot['content'], get_post( $snapshot['page_id'] )->post_content, 'Saved shortcodes preserved' );
if ( $formatting_failures ) {
	WP_CLI::error( sprintf( '%d/%d WordPress formatting checks failed.', $formatting_failures, $formatting_checks ) );
}
WP_CLI::success( sprintf( '%d WordPress formatting checks passed. Page: %s', $formatting_checks, get_permalink( $snapshot['page_id'] ) ) );
