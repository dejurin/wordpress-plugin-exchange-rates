<?php

namespace Dejurin\ExchangeRates\Admin\Pages;

use Dejurin\ExchangeRates\Plugin;

class Plugins
{
    public static function add_action_links($links)
    {
        return array_merge($links, [
            '<a href="'.admin_url('options-general.php?page='.Plugin::PLUGIN_SLUG.'-general').'">'.__('Settings', Plugin::PLUGIN_SLUG).'</a>',
        ]);
    }
}
