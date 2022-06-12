<?php

namespace Dejurin\ExchangeRates\Models;

class Dev
{
    public static function caption($caption_date)
    {
        $caption_date = ($caption_date) ? 'date' : 'time';
        $template = '<div class="sign"><p>%1$s <a href="%2$s">%3$s</a>, %4$s @ %5$s</p></div>';

        return $template;
    }
}
