<?php

namespace Dejurin\ExchangeRates;

class Widgets
{
    public static function register()
    {
        register_widget('Dejurin\ExchangeRates\Widgets\Table');
        register_widget('Dejurin\ExchangeRates\Widgets\Converter');
    }
}
