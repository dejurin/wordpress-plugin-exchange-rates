<?php

namespace Dejurin\ExchangeRates\Admin\Settings;

use Dejurin\ExchangeRates\Plugin;

class Pages
{
    /**
     * Register pages in General menu section.
     */
    public static function register_pages()
    {
        // General Settings
        add_submenu_page(
            'options-general.php',
            "\u{1F3E6} ".__('Exchange rates', Plugin::PLUGIN_SLUG),
            "\u{1F3E6} ".__('Exchange rates', Plugin::PLUGIN_SLUG),
            'manage_options',
            Plugin::PLUGIN_SLUG.'-general',
            ['\Dejurin\ExchangeRates\Admin\Settings\General\Page', 'render']
         );
    }
}
