<?php

namespace Dejurin\ExchangeRates;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/*
Plugin Name: Exchange Rates
Plugin URI: https://wordpress.org/plugins/exchange-rates/
Description: ❤️‍ It is a Currency Converter & Exchange Rates Widgets, easy-to-use, with beautiful UI. Included rates of 80+ world banks (sources).
Author: CurrencyRate.today
Author URI: https://currencyrate.today/
Version: 1.4.1
Text Domain: exchange-rates
Domain Path: /languages
Requires at least: 5.6
Requires PHP: 5.6
Tested up to: 7.1
License: GPLv2 or later
*/

/**
 * @version 1.4.1
 */
require_once __DIR__.'/vendor/autoload.php';

$GLOBALS['dejurin_exchange_rates'] = new Plugin(__FILE__);
$GLOBALS['dejurin_exchange_rates']->run();

/*
 * Activation process. Running only once.
 */
register_activation_hook(__FILE__, ['\Dejurin\ExchangeRates\Activation', 'run']);
register_deactivation_hook(__FILE__, ['\Dejurin\ExchangeRates\Deactivation', 'run']);
register_uninstall_hook(__FILE__, ['\Dejurin\ExchangeRates\Unistall', 'run']);
