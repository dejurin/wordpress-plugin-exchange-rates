<?php

namespace Dejurin\ExchangeRates\Admin\Settings;

class Loader
{
    /**
     * Register all WordPress settings pages.
     */
    public static function init()
    {
        add_action('admin_menu', ['\Dejurin\ExchangeRates\Admin\Settings\Pages', 'register_pages']);
        add_action('admin_init', ['\Dejurin\ExchangeRates\Admin\Settings\General\Page', 'init']);
    }
}
