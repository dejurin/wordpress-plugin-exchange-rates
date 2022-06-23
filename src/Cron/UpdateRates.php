<?php

namespace Dejurin\ExchangeRates\Cron;

use Dejurin\ExchangeRates\Plugin;

class UpdateRates
{
    public static $action_name = Plugin::PLUGIN_SLUG.'_data_update';

    public static function register_task()
    {
        // clear old
        wp_clear_scheduled_hook(self::$action_name);

        // add new
        wp_schedule_event(
            time(),
            'hourly',
            self::$action_name
        );
    }
}
