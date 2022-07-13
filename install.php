<?php

namespace Dejurin\ExchangeRates;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/*
Plugin Name: Exchange Rates
Plugin URI: https://wordpress.org/plugins/exchange-rates/
Description: Description: ❤️‍ It is a currency plugin, easy-to-use, with beautiful UI Widgets with shortcode supported. 🏦 Included exchange rates of 55 banks of the world.
Author: CurrencyRate.today
Author URI: https://currencyrate.today/
Version: 1.0.0
Text Domain: exchange-rates
Domain Path: /languages
Requires at least: 5.4
Tested up to: 6.0
License: GPLv2 or later
*/

/**
 * @version 1.0.0
 */
require_once 'vendor/autoload.php';

$GLOBALS['dejurin_exchange_rates'] = new Plugin(__FILE__);
$GLOBALS['dejurin_exchange_rates']->run();

/*
 * Activation process. Running only once.
 */
register_activation_hook(__FILE__, ['\Dejurin\ExchangeRates\Activation', 'run']);
register_deactivation_hook(__FILE__, ['\Dejurin\ExchangeRates\Deactivation', 'run']);
register_uninstall_hook(__FILE__, ['\Dejurin\ExchangeRates\Unistall', 'run']);
