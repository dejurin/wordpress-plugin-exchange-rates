<?php

namespace Dejurin\ExchangeRates;

class Unistall
{
    public static function run()
    {
        // Rates
        delete_option(Service\UpdateDataSources::$rates_option_name);

        // Widgets
        delete_option((new Widgets\Converter())->option_name);
        delete_option((new Widgets\Table())->option_name);

        // Settings
        delete_option(Models\Settings::$option_name);

        // Delete Transient
        delete_transient(Models\DataSources::$transient_option_name);

        // Delete Schedul
        wp_clear_scheduled_hook(Cron\UpdateRates::$action_name);
    }
}
