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

        /*
         * Update rates action.
         */
        add_action(Cron\UpdateRates::$action_name, ['\Dejurin\ExchangeRates\Service\UpdateDataSources', 'update']);

        /*
         * CODE START
         * Each HOUR_IN_SECONDS launches update data. In case the WP Cron doesn't work.
         * If WP Cron work fine, you need to commented or delete this one code below.
         */
        $settings = get_option(Models\Settings::$option_name, []);
        if ( ! empty( $settings ) && isset( $settings['update_timestamp'] )
            && intval( $settings['update_timestamp'] ) > 0
            && intval( $settings['update_timestamp'] ) + HOUR_IN_SECONDS < time() ) {
            Service\UpdateDataSources::update();
        }
        /*
         * CODE END
        */

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
