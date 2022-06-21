<?php

namespace Dejurin\ExchangeRates\Cron;

use Dejurin\ExchangeRates\Service\UpdateDataSources;

class UpdateRates
{
    public static function register_task()
    {
        wp_schedule_event(time(), 'hourly', UpdateDataSources::update());
    }
}
