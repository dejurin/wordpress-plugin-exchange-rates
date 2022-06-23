<?php

namespace Dejurin\ExchangeRates;

class Deactivation
{
    public static function run()
    {
        // Delete Transient
        delete_transient(Models\DataSources::$transient_option_name);

        // Delete Schedul
        wp_clear_scheduled_hook(Cron\UpdateRates::$action_name);
    }
}
