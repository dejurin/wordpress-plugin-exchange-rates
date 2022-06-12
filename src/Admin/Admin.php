<?php

namespace Dejurin\ExchangeRates\Admin;

use Dejurin\ExchangeRates\Plugin;

class Admin
{
    public static function run()
    {
        Settings\Loader::init();
        add_action( 'load-settings_page_' . Plugin::PLUGIN_SLUG.'-general', array( '\Dejurin\ExchangeRates\Admin\Settings\General\Page', 'update_rates_on_load' ) );
        add_filter('plugin_action_links_'.plugin_basename($GLOBALS['dejurin_exchange_rates']->plugin_path), ['\Dejurin\ExchangeRates\Admin\Pages\Plugins', 'add_action_links']);
    }
}
