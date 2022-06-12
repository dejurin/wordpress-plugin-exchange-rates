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
            ['name' => 'None', 'value' => 'none'],
            ['name' => 'Rectangular', 'value' => 'rectangular'],
            ['name' => 'Square', 'value' => 'square'],
            ['name' => 'Circular', 'value' => 'circular'],
            ['name' => 'Emoji', 'value' => 'emoji'],
        ];
    }
}
