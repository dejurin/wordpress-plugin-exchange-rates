<?php

namespace Dejurin\ExchangeRates\Cron;

use Dejurin\ExchangeRates\Plugin;

class UpdateRates
{
    public static $action_name = Plugin::PLUGIN_SLUG.'_data_update';

    public static function register_task()
    {
        // add new
        if (!wp_next_scheduled(self::$action_name)) {
            wp_schedule_event(
                time(),
                'hourly',
                self::$action_name
            );
        }
    }
}
