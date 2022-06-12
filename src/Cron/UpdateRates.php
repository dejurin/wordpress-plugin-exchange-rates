<?php

namespace Dejurin\ExchangeRates\Cron;

class UpdateCurrency
{
    public static $action_name = '_update_rates';

    public static function register_task()
    {
        wp_schedule_event(time(), 'hourly', \Dejurin\ExchangeRates\Plugin::NAME.self::$action_name);
    }
}
