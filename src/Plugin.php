<?php

namespace Dejurin\ExchangeRates;

use Dejurin\ExchangeRates\Admin\Admin;

class Plugin
{
    public const VERSION = '0.0.1';
    public const NAME = 'Exchange Rates';
    public const PLUGIN_SLUG = 'exchange-rates';
    public $plugin_path = '';

    public function __construct($run_from_file)
    {
        $this->plugin_path = $run_from_file;
    }

    public function run()
    {   /*
         * Admin Register Script & Styles
         */
        add_action('admin_enqueue_scripts', [$this, 'registerAdminScriptStyle']);
        add_action('wp_enqueue_scripts', [$this, 'registerPublicScriptStyle']);
        add_action('widgets_init', ['Dejurin\ExchangeRates\Widgets', 'register']);

        if (is_admin()) {
            Admin::run();
        }
    }

    public function hide_example_widget($widget_types)
    {
        $widget_types[] = Plugin::PLUGIN_SLUG;

        return $widget_types;
    }

    public function registerPublicScriptStyle()
    {
        wp_register_style(
            'plugin-'.Plugin::PLUGIN_SLUG.'-widgets',
            plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/css/public/style.css',
            [],
            self::VERSION
        );
        wp_register_script(
            'plugin-'.Plugin::PLUGIN_SLUG.'-widgets',
            plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/js/public/widget.js',
            ['jquery-ui-autocomplete'],
            self::VERSION,
            true
        );
        wp_enqueue_script('plugin-'.Plugin::PLUGIN_SLUG.'-widgets');
        wp_enqueue_style('plugin-'.Plugin::PLUGIN_SLUG.'-widgets');
    }

    public function registerAdminScriptStyle()
    {
        // Register all scripts & styles
        wp_register_style(
            'plugin-'.Plugin::PLUGIN_SLUG.'-widgets-settings',
            plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/css/admin/style.css',
            [],
            self::VERSION
        );
        wp_register_script(
            'plugin-'.Plugin::PLUGIN_SLUG.'-widgets-admin-common',
            plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/js/admin/common.js',
            ['jquery'],
            self::VERSION,
            true
        );
        wp_enqueue_style('plugin-'.Plugin::PLUGIN_SLUG.'-widgets-settings');
        wp_enqueue_script('plugin-'.Plugin::PLUGIN_SLUG.'-widgets-admin-common');
    }
}
