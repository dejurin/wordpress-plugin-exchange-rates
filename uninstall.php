<?php

namespace Dejurin\ExchangeRates;

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

/**
 * Autoloader for all classes.
 *
 * @since 0.0.1
 */
require_once 'vendor/autoload.php';

delete_option(Plugin::PLUGIN_SLUG.'_rates');
delete_option(Plugin::PLUGIN_SLUG.'_providers');
delete_option(Settings::$option_name);
// delete_transient(DataProviders::getInstance()->get_transient_name());
