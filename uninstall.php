<?php

namespace Dejurin\ExchangeRates;

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Widgets\Converter;
use Dejurin\ExchangeRates\Widgets\Table;

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

// Rates
delete_option(\Dejurin\ExchangeRates\Service\UpdateDataSources::$rates_option_name);

// Widgets
delete_option((new Converter())->option_name);
delete_option((new Table())->option_name);

// Settings
delete_option(Settings::$option_name);

// Delete Transient
delete_transient(\Dejurin\ExchangeRates\Models\DataSources::$transient_option_name);
