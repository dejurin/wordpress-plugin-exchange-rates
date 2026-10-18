<?php

namespace Dejurin\ExchangeRates;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @version 1.4.0
 */

use Dejurin\ExchangeRates\Admin\Notices;


class Plugin
{
    const VERSION = '1.4.0';
    const NAME = 'Exchange Rates';
    const PLUGIN_SLUG = 'exchange-rates';
    public $plugin_path = '';

    public function __construct($run_from_file)
    {
        $this->plugin_path = $run_from_file;
    }

    public function run()
    {   /*
         * Backend and Frontend Register Script & Styles
         */
        add_action('admin_enqueue_scripts', [$this, 'register_admin_script_style']);
        add_action('widgets_init', ['\Dejurin\ExchangeRates\Widgets', 'register']);

        $admin_notice = Notices::get_instance();
        $admin_notice->info('Rate', 'rate');

        // Keep recurring updates and recover expired/missing data in WP-Cron only.
        add_action( Cron\UpdateRates::$action_name, array( '\\Dejurin\\ExchangeRates\\Cron\\UpdateRates', 'run_hourly' ) );
        add_action( Cron\UpdateRates::$retry_action_name, array( '\\Dejurin\\ExchangeRates\\Cron\\UpdateRates', 'run_retry' ) );
        Cron\UpdateRates::register_task();
        Cron\UpdateRates::queue_if_due();

        /*
         * Add Badge shortcode.
         */

        new Shortcodes\Badge();
        new Shortcodes\CurrencyTable();
        new Shortcodes\CurrencyConverter();

        if (is_admin()) {
            Admin\Admin::run();
        }
    }

    /*
    public function hide_example_widget($widget_types)
    {
        $widget_types[] = Plugin::PLUGIN_SLUG;

        return $widget_types;
    }
    */

    public static function register_public_script_style()
    {
        wp_register_style(
            'plugin-'.Plugin::PLUGIN_SLUG.'-widgets',
            plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/css/public/style.css',
            [],
            self::VERSION
        );
        wp_register_script(
            'plugin-'.Plugin::PLUGIN_SLUG.'-widgets',
            plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/js/public/common.js',
            ['jquery-ui-autocomplete'],
            self::VERSION,
            true
        );
        wp_enqueue_style('plugin-'.Plugin::PLUGIN_SLUG.'-widgets');
        wp_enqueue_script('plugin-'.Plugin::PLUGIN_SLUG.'-widgets');
    }

    public function register_admin_script_style($hook)
    {
        // Load plugin admin assets only on the screens that actually use them:
        // the plugin settings page and the widgets screen. Enqueueing them
        // globally also injected the stylesheet into the block editor iframe
        // on post.php / post-new.php.
        if (!in_array($hook, ['settings_page_exchange-rates-general', 'widgets.php'], true)) {
            return;
        }

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
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
}
