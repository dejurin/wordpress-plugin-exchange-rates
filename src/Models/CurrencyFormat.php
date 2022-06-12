<?php

namespace Dejurin\ExchangeRates\Models;

class CurrencyFormat
{
    public static function get_list()
    {
        return [
                [
                    'name' => '1.234,56',
                    'decimal_point' => ',',
                    'thousands_sep' => '.',
                ],
                [
                    'name' => '1 234.56',
                    'decimal_point' => '.',
                    'thousands_sep' => ' ',
                ],
                [
                    'name' => '1 234,56',
                    'decimal_point' => ',',
                    'thousands_sep' => ' ',
                ],
                [
                    'name' => '1,234.56',
                    'decimal_point' => '.',
                    'thousands_sep' => ',',
                ],
                [
                    'name' => "1'234.56",
                    'decimal_point' => '.',
                    'thousands_sep' => "'",
                ],
                [
                    'name' => "1'234,56",
                    'decimal_point' => ',',
                    'thousands_sep' => "'",
                ],
                [
                    'name' => '1234.56',
                    'decimal_point' => '.',
                    'thousands_sep' => '',
                ],
                [
                    'name' => '1234,56',
                    'decimal_point' => ',',
                    'thousands_sep' => '',
                ],
        ];
    }
}
