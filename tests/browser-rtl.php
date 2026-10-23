<?php
/** Read-only rendering fixtures for bidi browser regressions. */

if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	return;
}

add_action( 'init', function () {
	if ( ! isset( $_GET['er-bidi-test'] ) ) {
		return;
	}
	$GLOBALS['wp_locale']->text_direction = isset( $_GET['direction'] ) && 'rtl' === $_GET['direction'] ? 'rtl' : 'ltr';
}, 0 );

add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['er-bidi-test'] ) ) {
		return;
	}
	$format = isset( $_GET['format'] ) ? max( 0, min( 7, (int) $_GET['format'] ) ) : 0;
	$after = isset( $_GET['after'] ) && '1' === $_GET['after'];
	add_filter( 'option_exchange-rates', function ( $settings ) use ( $format ) {
		$settings['currency_format'] = $format;
		$settings['decimals'] = 2;
		return $settings;
	} );
	$common = array( 'amount' => '1234.56', 'symbol' => true, 'after' => $after, 'flag_type' => 'none', 'code' => true );
	$table = array_merge( $common, array( 'currency_list' => array( 'EUR', 'BRL' ), 'base_currency' => 'USD', 'base_show' => true, 'amount_active' => true, 'table_headers_previous_close_show' => true ) );
	$converter = array_merge( $common, array( 'from' => 'USD', 'to' => 'BRL' ) );
	$position = $after ? 'true' : 'false';
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo '<!doctype html><html lang="en" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"><head><meta name="viewport" content="width=device-width, initial-scale=1">';
	wp_head();
	echo '<style>body{margin:16px;font-family:Arial,sans-serif}section{max-width:640px;margin-bottom:24px}</style></head><body>';
	echo '<h1>Numeric direction regression fixture</h1><p>Local synthetic rates only. العربية עברית</p>';
	echo '<section id="shortcode-table">';
	echo do_shortcode( '[exchange-rates_currency-table amount="1234.56" currency_list="EUR,BRL" base_show="true" amount_active="true" flag_type="none" code="true" symbol="true" after="' . $position . '" table_headers_previous_close_show="true"]' );
	echo '</section><section id="widget-table">';
	the_widget( '\Dejurin\ExchangeRates\Widgets\Table', $table );
	echo '</section><section id="static-table">';
	echo do_shortcode( '[exchange-rates_currency-table amount="1234.56" currency_list="EUR" base_show="true" amount_active="false" flag_type="none" code="true"]' );
	echo '</section><section id="shortcode-converter">';
	echo do_shortcode( '[exchange-rates_currency-converter amount="1234.56" from="USD" to="BRL" symbol="true" after="' . $position . '"]' );
	echo '</section><section id="widget-converter">';
	the_widget( '\Dejurin\ExchangeRates\Widgets\Converter', $converter );
	echo '</section><section id="badges">';
	echo do_shortcode( '[exchange-rates_badge amount="1234.56" currency_list="EUR,BRL" flag_type="none" code="true" decimals="2" symbol="true" after="' . $position . '"]' );
	echo '</section>';
	wp_footer();
	echo '</body></html>';
	exit;
} );

// Run before the numeric fixture and reuse its guarded direction setup.
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['er-bidi-test'], $_GET['labels'] ) ) {
		return;
	}
	$flag = isset( $_GET['flag'] ) && in_array( $_GET['flag'], array( 'rectangular', 'square', 'circular', 'emoji', 'none' ), true ) ? $_GET['flag'] : 'rectangular';
	$size = isset( $_GET['size'] ) ? max( 16, min( 64, (int) $_GET['size'] ) ) : 16;
	$code = ! isset( $_GET['names'] );
	$region = ! isset( $_GET['no-region'] );
	$language = isset( $_GET['language'] ) ? $_GET['language'] : 'en';
	$translations = array(
		'ar' => array( 'Euro' => 'يورو', 'European Union' => 'الاتحاد الأوروبي', 'Brazilian Real' => 'ريال برازيلي', 'Brazil' => 'البرازيل', 'US Dollar' => 'دولار أمريكي', 'United States' => 'الولايات المتحدة' ),
		'he' => array( 'Euro' => 'אירו', 'European Union' => 'האיחוד האירופי', 'Brazilian Real' => 'ריאל ברזילאי', 'Brazil' => 'ברזיל', 'US Dollar' => 'דולר אמריקאי', 'United States' => 'ארצות הברית' ),
		'long' => array( 'Euro' => 'An intentionally very long currency name for truncation coverage', 'European Union' => 'An intentionally very long region name for truncation coverage' ),
	);
	if ( isset( $translations[ $language ] ) ) {
		$dictionary = $translations[ $language ];
		add_filter( 'gettext', function ( $translated, $text, $domain ) use ( $dictionary ) {
			return 'exchange-rates' === $domain && isset( $dictionary[ $text ] ) ? $dictionary[ $text ] : $translated;
		}, 10, 3 );
	}
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo '<!doctype html><html lang="' . esc_attr( in_array( $language, array( 'ar', 'he' ), true ) ? $language : 'en' ) . '" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"><head><meta name="viewport" content="width=device-width, initial-scale=1">';
	wp_head();
	echo '<style>body{margin:16px;font-family:Arial,sans-serif}section{max-width:640px;margin-bottom:24px}</style></head><body>';
	echo '<h1>Flag and label regression fixture</h1><p>Local synthetic rates only.</p>';
	foreach ( array( true, false ) as $full_width ) {
		$attributes = array( 'amount' => 1, 'base_currency' => 'USD', 'currency_list' => 'EUR,BRL', 'base_show' => true, 'flag_type' => $flag, 'flag_size' => $size, 'code' => $code, 'region' => $region, 'full_width' => $full_width );
		$suffix = $full_width ? 'full' : 'compact';
		$shortcode = '[exchange-rates_currency-table';
		foreach ( $attributes as $name => $value ) {
			$shortcode .= ' ' . $name . '="' . esc_attr( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '"';
		}
		echo '<section id="shortcode-' . $suffix . '"><h2>Shortcode: ' . $suffix . '</h2>' . do_shortcode( $shortcode . ']' ) . '</section>';
		echo '<section id="widget-' . $suffix . '"><h2>Native widget: ' . $suffix . '</h2>';
		$attributes['currency_list'] = array( 'EUR', 'BRL' );
		the_widget( '\Dejurin\ExchangeRates\Widgets\Table', $attributes );
		echo '</section>';
	}
	wp_footer();
	echo '</body></html>';
	exit;
}, 9 );
