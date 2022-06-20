<?php

namespace Dejurin\ExchangeRates;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/*
Plugin Name: Exchange Rates
Plugin URI: https://wordpress.org/plugins/exchange-rates/
Description: Currency widgets for any needs.
Author: CurrencyRate.today
Author URI: https://currencyrate.today/
Version: 0.0.1
Text Domain: currency-widgets-all-banks
Domain Path: /languages/
Requires at least: 5.4
Tested up to: 6.0
License: GPLv2 or later
*/

/**
 * @version 0.0.1
 */
require_once 'vendor/autoload.php';

$GLOBALS['dejurin_exchange_rates'] = new Plugin(__FILE__);
$GLOBALS['dejurin_exchange_rates']->run();

/*
 * Activation process. Running only once.
 */
register_activation_hook(__FILE__, ['\Dejurin\ExchangeRates\Activation', 'run']);
