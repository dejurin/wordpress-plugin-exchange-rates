<?php

namespace Dejurin\ExchangeRates\Models;

class DateFormat
{
    public static function get_list()
    {
        return [
            ['name' => 'Canadian', 'format' => 'yyyy-mm-dd'],
            ['name' => 'Danish', 'format' => 'yyyy-mm-dd'],
            ['name' => 'Finnish', 'format' => 'dd.mm.yyyy'],
            ['name' => 'French', 'format' => 'dd/mm/yyyy'],
            ['name' => 'German', 'format' => 'yyyy-mm-dd'],
            ['name' => 'Italian', 'format' => 'dd.mm.yy'],
            ['name' => 'Norwegian', 'format' => 'dd.mm.yy'],
            ['name' => 'Spanish', 'format' => 'dd-mm-yy'],
            ['name' => 'Swedish', 'format' => 'yyyy-mm-dd'],
            ['name' => 'GB-English', 'format' => 'dd/mm/yy'],
            ['name' => 'US-English', 'format' => 'mm-dd-yy'],
            ['name' => 'Thai', 'format' => 'dd/mm/yyyy'],
          ];
    }
}
