<?php

namespace Dejurin\ExchangeRates\Models;

class Flags
{
    public static function get_sizes($rectangular = true)
    {
        $ratio = ($rectangular) ? 4 * 3 : 1;

        return [
            ['width' => 16, 'height' => 16 / $ratio],
            ['width' => 24, 'height' => 24 / $ratio],
            ['width' => 32, 'height' => 32 / $ratio],
            ['width' => 48, 'height' => 48 / $ratio],
            ['width' => 64, 'height' => 64 / $ratio],
        ];
    }

    public static function get_types()
    {
        return [
            ['name' => esc_html__('None', 'exchange-rates'), 'value' => 'none'],
            ['name' => esc_html__('Rectangular', 'exchange-rates'), 'value' => 'rectangular'],
            ['name' => esc_html__('Square', 'exchange-rates'), 'value' => 'square'],
            ['name' => esc_html__('Circular', 'exchange-rates'), 'value' => 'circular'],
            ['name' => esc_html__('Emoji', 'exchange-rates'), 'value' => 'emoji'],
        ];
    }
}
