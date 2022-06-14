<?php

namespace Dejurin\ExchangeRates;

class Activation
{
    public static function run()
    {
        /*
         * Cron task for update currency rates each hour.
         */

        // Cron\UpdateRates::register_task();

        Service\UpdateDataSources::update();
    }
}
