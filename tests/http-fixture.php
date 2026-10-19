<?php
/**
 * Plugin Name: Exchange Rates synthetic test data (local tests only)
 */

if (!defined('ABSPATH') || '1' !== getenv('EXCHANGE_RATES_TEST_MODE') || 'local' !== wp_get_environment_type()) {
    return;
}

// The isolated fixture cannot answer update checks against WordPress.org.
foreach (array('_maybe_update_core', '_maybe_update_plugins', '_maybe_update_themes') as $callback) {
    remove_action('admin_init', $callback);
}

add_filter('pre_http_request', function ($preempt, $args, $url) {
    if (!in_array($url, array('https://api-bank.fex.to/rates/currencyrate.json', 'https://api-bank.fex.to/sources.json'), true)) {
        return new WP_Error('test_network_disabled', 'Outbound WordPress HTTP requests are disabled in this test environment.');
    }

    // Active only while the guarded first-load browser fixture owns a snapshot.
    $manual = get_option('exchange_rates_empty_update_provider');
    if (is_array($manual) && 'https://api-bank.fex.to/rates/currencyrate.json' === $url) {
        $manual['calls']++;
        update_option('exchange_rates_empty_update_provider', $manual);
        if ('failure' === $manual['mode']) {
            return new WP_Error('test_provider_down', 'Synthetic provider unavailable.');
        }
    }

    $payload = array(
        'source' => 'currencyrate',
        'base' => 'USD',
        'data' => array(
            array('local_time' => '2026-01-02 12:00:00', 'put_time' => '2026-01-02 12:01:00',
                'rates' => array('USD' => 1, 'EUR' => 1.25, 'GBP' => 1.5, 'AUD' => 0.75, 'JPY' => 0.01, 'BRL' => 0.2)),
            array('local_time' => '2026-01-01 12:00:00', 'put_time' => '2026-01-01 12:01:00',
                'rates' => array('USD' => 1, 'EUR' => 1.2, 'GBP' => 1.4, 'AUD' => 0.7, 'JPY' => 0.009, 'BRL' => 0.19)),
        ),
    );

    if ('https://api-bank.fex.to/sources.json' === $url) {
        $payload = array(array('source' => 'currencyrate', 'country' => 'currencyrate', 'base' => 'USD', 'lastupdate' => 1767355200));
    }

    return array('headers' => array('content-type' => 'application/json'), 'body' => wp_json_encode($payload),
        'response' => array('code' => 200, 'message' => 'OK'), 'cookies' => array());
}, 10, 3);

// Exercise the actual widget forms without depending on the active theme's widget screen.
add_action( 'admin_menu', function () {
	add_management_page( 'Widget form regressions', 'Widget form regressions', 'manage_options', 'exchange-rates-test-widget-forms', function () {
		$table_options = get_option( 'widget_exchange-rates_currency-table' );
		$fixtures = array(
			'table' => array( new \Dejurin\ExchangeRates\Widgets\Table(), $table_options[2] ),
			'converter' => array( new \Dejurin\ExchangeRates\Widgets\Converter(), array( 'title' => 'Test converter', 'amount' => 7, 'from' => 'USD', 'to' => 'EUR' ) ),
		);
		foreach ( $fixtures as $name => $fixture ) {
			$widget = $fixture[0];
			$widget->_set( 2 );
			echo '<form id="test-widget-' . esc_attr( $name ) . '">';
			echo '<input type="hidden" name="id_base" value="' . esc_attr( $widget->id_base ) . '">';
			$widget->form( $fixture[1] );
			echo '</form>';
		}
	} );
} );
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'tools_page_exchange-rates-test-widget-forms' === $hook ) {
		$GLOBALS['dejurin_exchange_rates']->register_admin_script_style( 'widgets.php' );
	}
} );

// Browser-only fixtures render the real shortcodes and widget, not copied markup.
add_shortcode( 'exchange_rates_table_test', function ( $attributes ) {
	$mode = isset( $attributes['mode'] ) ? $attributes['mode'] : 'mixed';
	$tables = array(
		'first'  => array( 'format' => 3, 'decimals' => 2 ),
		'second' => array( 'format' => 0, 'decimals' => 3 ),
		'static' => array( 'format' => 3, 'decimals' => 2 ),
	);
	if ( 'single' === $mode ) {
		$tables = array( 'first' => $tables['first'] );
	} elseif ( 'reversed' === $mode ) {
		$tables = array_reverse( $tables, true );
	}

	$html = '';
	foreach ( $tables as $name => $format ) {
		// Rendering reads global settings; vary them per instance without saving options.
		$filter = function ( $settings ) use ( $format ) {
			$settings['currency_format'] = $format['format'];
			$settings['decimals'] = $format['decimals'];
			return $settings;
		};
		add_filter( 'option_exchange-rates', $filter );
		$html .= '<section data-table-fixture="' . esc_attr( $name ) . '">';
		if ( 'second' === $name ) {
			$widgets = get_option( 'widget_exchange-rates_currency-table' );
			ob_start();
			the_widget( '\Dejurin\ExchangeRates\Widgets\Table', $widgets[2], array( 'before_widget' => '<div>', 'after_widget' => '</div>' ) );
			$html .= ob_get_clean();
		} elseif ( 'static' === $name ) {
			$html .= do_shortcode( '[exchange-rates_currency-table amount="5" currency_list="AUD" flag_type="none" code="true" amount_active="false"]' );
		} else {
			$html .= do_shortcode( '[exchange-rates_currency-table amount="2" currency_list="EUR" flag_type="none" code="true" after="false" amount_active="true" table_headers_previous_close_show="true"]' );
		}
		$html .= '</section>';
		remove_filter( 'option_exchange-rates', $filter );
	}
	return $html . '<section data-table-fixture="controls">'
		. do_shortcode( '[exchange-rates_badge currency_list="EUR" flag_type="none"] [exchange-rates_currency-converter from="USD" to="EUR"]' )
		. '</section>';
} );

// Exercise real WordPress RTL admin assets without installing a language pack.
add_action('init', function () {
    if (get_option('exchange_rates_empty_update_provider') && isset($_GET['er-test-direction']) && 'rtl' === $_GET['er-test-direction']) {
        $GLOBALS['wp_locale']->text_direction = 'rtl';
        wp_styles()->text_direction = 'rtl';
    }
});
