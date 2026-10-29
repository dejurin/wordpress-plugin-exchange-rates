<?php

namespace Dejurin\ExchangeRates\Models;

if (!defined('ABSPATH')) {
    exit;
}

// use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Plugin;

class Dev
{
	/** Preserve public controls when sanitizing native widgets on WordPress 5.6. */
	public static function allowed_widget_html() {
		$allowed = wp_kses_allowed_html( 'post' );
		$allowed['input'] = array( 'value' => true, 'type' => true, 'name' => true, 'id' => true, 'aria-label' => true );
		$allowed['button'] = array( 'type' => true, 'class' => true, 'title' => true, 'aria-label' => true, 'aria-haspopup' => true, 'aria-controls' => true, 'data-er-info' => true, 'data-recorded-at' => true, 'data-source-name' => true, 'data-source-url' => true );
		return $allowed;
	}

	/** Render the existing unavailable message when no current cache exists. */
	public static function rates_unavailable() {
		return '<b data-exchange-rates-version="' . esc_attr( Plugin::VERSION ) . '">' . Plugin::NAME . '</b> ' . esc_html__( 'Error: exchange-rate data is not available yet.', 'exchange-rates' );
	}

    public static function caption($parameters, $time, $widget_nubmer, $widget_slug, $source_id, $dev = 1)
    {
        return \Dejurin\ExchangeRates\Service\Footer::caption($parameters, $time, $source_id, $dev);
    }
}
