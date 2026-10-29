<?php
/** Real WordPress rendering for the focused visual regressions. */

if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	return;
}

add_action( 'init', function () {
	if ( ! isset( $_GET['er-visual-test'] ) ) {
		return;
	}
	$GLOBALS['wp_locale']->text_direction = isset( $_GET['direction'] ) && 'rtl' === $_GET['direction'] ? 'rtl' : 'ltr';
	add_filter( 'pre_option_exchange-rates', function () {
		return array_merge( \Dejurin\ExchangeRates\Models\Settings::get_defaults(), array( 'source_id' => 'currencyrate', 'base_currency' => 'USD', 'currency_format' => 3, 'decimals' => 4, 'rates_available' => true, 'update_timestamp' => time() ) );
	} );
	add_filter( 'pre_option_exchange-rates_rates', function () {
		$current = array( 'USD' => 1, 'EUR' => 0.92, 'RON' => 4.92, 'BAM' => 1.8, 'ZAR' => 17.3 );
		return array( 'source' => 'currencyrate', 'base' => 'USD', 'data' => array(
			array( 'local_time' => '2026-10-22 22:16:00', 'put_time' => '2026-10-25 00:10:00', 'rates' => $current ),
			array( 'local_time' => '2026-10-21 22:16:00', 'put_time' => '2026-10-24 00:10:00', 'rates' => $current ),
		) );
	} );
}, 0 );

add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['er-visual-test'] ) ) {
		return;
	}
	$surface = isset( $_GET['surface'] ) ? $_GET['surface'] : 'table';
	$font = isset( $_GET['font'] ) && '22' === $_GET['font'] ? 22 : 16;
	$size = isset( $_GET['size'] ) ? max( 16, min( 64, (int) $_GET['size'] ) ) : 32;
	$flag = isset( $_GET['no-flag'] ) ? 'none' : 'circular';
	if ( isset( $_GET['language'] ) && in_array( $_GET['language'], array( 'uk', 'bg_BG' ), true ) ) {
		unload_textdomain( 'exchange-rates' );
		load_textdomain( 'exchange-rates', WP_PLUGIN_DIR . '/exchange-rates/languages/exchange-rates-' . $_GET['language'] . '.mo' );
		// Construct after loading the catalog, as WordPress does for a new request.
		$shortcode = new \Dejurin\ExchangeRates\Shortcodes\CurrencyConverter();
	}
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo '<!doctype html><html lang="en" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"><head><meta name="viewport" content="width=device-width,initial-scale=1">';
	wp_head();
	// A theme baseline reproduces the reported cross-cell alignment defect.
	echo '<style>body{margin:16px;font:' . (int) $font . 'px/1.5 Arial,sans-serif;color:#333;background:#fff}section{max-width:640px;margin:0 0 24px}h1{font-size:20px}h2{font-size:17px}th,td{vertical-align:baseline;font-weight:400}button,input,select{font:inherit}</style></head><body>';
	echo '<h1>Visual regression fixtures — synthetic rates</h1>';
	if ( 'table' === $surface ) {
		$attributes = array( 'base_currency' => 'EUR', 'currency_list' => 'RON,USD', 'base_show' => true, 'flag_type' => $flag, 'flag_size' => $size, 'table_headers_code_show' => true, 'region' => isset( $_GET['region'] ), 'amount_active' => isset( $_GET['editable'] ), 'border' => false );
		foreach ( array( 'shortcode', 'widget' ) as $kind ) {
			echo '<section id="' . esc_attr( $kind ) . '-table"><h2>' . esc_html( ucfirst( $kind ) . ' table' ) . '</h2>';
			if ( 'shortcode' === $kind ) {
				$config = '[exchange-rates_currency-table';
				foreach ( $attributes as $name => $value ) {
					$config .= ' ' . $name . '="' . esc_attr( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '"';
				}
				echo do_shortcode( $config . ']' );
			} else {
				$instance = $attributes;
				$instance['currency_list'] = array( 'RON', 'USD' );
				the_widget( '\Dejurin\ExchangeRates\Widgets\Table', $instance );
			}
			echo '</section>';
		}
	} elseif ( 'badge' === $surface ) {
		foreach ( array( 'simple' => 'false', 'base' => 'true' ) as $name => $show_base ) {
			echo '<section id="badge-' . esc_attr( $name ) . '"><h2>' . esc_html( ucfirst( $name ) . ' badge' ) . '</h2>';
			echo do_shortcode( '[exchange-rates_badge currency_list="BAM,ZAR" base_show="' . $show_base . '" flag_type="' . esc_attr( $flag ) . '" amount="1234567.89" symbol="true"]' );
			echo '</section>';
		}
	} elseif ( 'converter' === $surface ) {
		$title_filter = isset( $_GET['title-filter'] ) ? $_GET['title-filter'] : '';
		if ( in_array( $title_filter, array( 'custom', 'empty' ), true ) ) {
			add_filter( 'shortcode_atts_exchange-rates_currency-converter', function ( $attributes, $defaults, $atts ) use ( $title_filter ) {
				if ( ! array_key_exists( 'title', $atts ) ) {
					$attributes['title'] = 'custom' === $title_filter ? 'Site converter' : '';
				}
				return $attributes;
			}, 10, 3 );
		}
		$cases = array( 'default' => '', 'custom' => ' title="Saved custom title"', 'empty' => ' title=""' );
		foreach ( $cases as $name => $title ) {
			echo '<section id="converter-' . esc_attr( $name ) . '"><h2>' . esc_html( ucfirst( $name ) . ' converter' ) . '</h2>';
			echo do_shortcode( '[exchange-rates_currency-converter from="BAM" to="ZAR" amount="1234567.89" symbol="true"' . $title . ']' );
			echo '</section>';
		}
		echo '<section id="converter-widget"><h2>Native converter widget</h2>';
		the_widget( '\Dejurin\ExchangeRates\Widgets\Converter', array( 'title' => 'Saved widget title', 'from' => 'BAM', 'to' => 'ZAR', 'amount' => 1234567.89, 'symbol' => true ) );
		echo '</section>';
	}
	wp_footer();
	echo '</body></html>';
	exit;
} );
