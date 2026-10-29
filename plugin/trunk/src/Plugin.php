<?php

namespace Dejurin\ExchangeRates;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @version 1.5.0
 */

use Dejurin\ExchangeRates\Admin\Notices;


class Plugin
{
    const VERSION = '1.5.0';
    const NAME = 'Exchange Rates';
    const PLUGIN_SLUG = 'exchange-rates';
    public $plugin_path = '';

    public function __construct($run_from_file)
    {
        $this->plugin_path = $run_from_file;
    }

    public function run()
    {
        Service\RefreshLifecycle::generation();
        /*
         * Backend and Frontend Register Script & Styles
         */
        add_action('admin_enqueue_scripts', [$this, 'register_admin_script_style']);
        add_action('widgets_init', ['\Dejurin\ExchangeRates\Widgets', 'register']);

        $admin_notice = Notices::get_instance();
        $admin_notice->info('Rate', 'rate');

        add_filter( 'cron_schedules', array( '\\Dejurin\\ExchangeRates\\Cron\\UpdateRates', 'register_schedule' ) );

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
            ['jquery'],
            self::VERSION,
            true
        );
        wp_enqueue_style('plugin-'.Plugin::PLUGIN_SLUG.'-widgets');
        wp_enqueue_script('plugin-'.Plugin::PLUGIN_SLUG.'-widgets');
        $asset_url = plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path) . 'assets/';
        wp_enqueue_style('plugin-exchange-rates-footer', $asset_url . 'css/public/footer.css', array('plugin-exchange-rates-widgets', 'dashicons'), self::VERSION);
        wp_enqueue_script('plugin-exchange-rates-footer', $asset_url . 'js/public/footer.js', array('plugin-exchange-rates-widgets'), self::VERSION, true);
        add_action('wp_print_footer_scripts', array(__CLASS__, 'localize_public_messages'), 0);
    }

    /** Resolve translations after the page has selected its locale. */
    public static function localize_public_messages()
    {
        wp_localize_script('plugin-exchange-rates-widgets', 'exchangeRatesMessages', array(
            'invalidAmount' => __('Enter a valid amount using a dot for decimals.', 'exchange-rates'),
            'unable' => __('Unable to calculate. Enter a valid amount.', 'exchange-rates'),
            'unavailable' => __('unavailable', 'exchange-rates'),
            'notAvailable' => __('Not available', 'exchange-rates'),
            'unknownSource' => __('Unknown source', 'exchange-rates'),
            'details' => __('Data details and disclaimer', 'exchange-rates'),
            'rateDate' => __('Rate date', 'exchange-rates'),
            /* translators: %s: ISO currency code. */
            'amountIn' => __('Amount in %s', 'exchange-rates'),
            'inverseAmount' => __('Amount in each listed currency', 'exchange-rates'),
            /* translators: %s: ISO currency code. */
            'ratesIn' => __('Exchange rates in %s', 'exchange-rates'),
            /* translators: %s: ISO currency code. */
            'ratesFor' => __('Exchange rates for %s', 'exchange-rates'),
            /* translators: 1: amount, 2: ISO currency code. */
            'ratesAmount' => __('Rates for %1$s %2$s.', 'exchange-rates'),
            /* translators: %s: ISO currency code. */
            'previousClose' => __('%s previous close', 'exchange-rates'),
            'tableScrollHint' => __('Scroll horizontally to see all columns.', 'exchange-rates'),
            'tableScrollRegion' => __('Exchange rates, horizontally scrollable', 'exchange-rates'),
            /* translators: 1: amount, 2: source currency, 3: result, 4: target currency. */
            'result' => __('%1$s %2$s equals %3$s %4$s', 'exchange-rates'),
        ));
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
