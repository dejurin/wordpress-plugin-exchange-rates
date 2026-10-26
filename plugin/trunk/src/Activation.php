<?php

namespace Dejurin\ExchangeRates;

if (!defined('ABSPATH')) {
    exit;
}

class Activation
{
    public static function run()
    {
        Service\RefreshLifecycle::activate();
        /*
         * Cron task for update currency rates every 30 minutes.
         */
        Cron\UpdateRates::register_task();

        // Update once
        Service\UpdateDataSources::update();
    }
}
