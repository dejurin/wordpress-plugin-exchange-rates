<?php

namespace Dejurin\ExchangeRates;

if (!defined('ABSPATH')) {
    exit;
}

class Deactivation
{
    public static function run()
    {
        // Delete Transient
        delete_transient(Models\DataSources::$transient_option_name);

        // Delete Schedul
        Cron\UpdateRates::clear_tasks();
    }
}
