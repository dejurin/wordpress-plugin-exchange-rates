<?php

namespace Dejurin\ExchangeRates\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

class Admin
{
    public static function run()
    {
        Settings\Loader::init();

        // Enqueue JavaScript
        add_action('admin_enqueue_scripts', function() {
            $file = '../../assets/js/admin/force-data-update.js';
            $script_url = plugin_dir_url(__FILE__) . $file;
            $script_path = plugin_dir_path(__FILE__) . $file;
            wp_enqueue_script('exchange-rates-force-data-update-js', $script_url, array('jquery'), filemtime($script_path), true);
            wp_localize_script('exchange-rates-force-data-update-js', 'ExchangeRatesForceDataUpdateAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('exchange_rates_force_data_update_nonce'),
            ));
        });

        // Register the action hook for handling the AJAX request
        add_action('wp_ajax_exchange_rates_force_data_update', function() {
            check_ajax_referer('exchange_rates_force_data_update_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Access denied!']);
            }

            $settings = get_option(\Dejurin\ExchangeRates\Models\Settings::$option_name, []);
            $settings = wp_parse_args($settings, \Dejurin\ExchangeRates\Models\Settings::get_defaults());

            $current_time = current_time('timestamp');
            $last_update_time = isset($settings['update_timestamp']) ? (int) $settings['update_timestamp'] : 0;
            $update_delay_seconds = 60;

            // Check if the last update was less than 60 seconds ago
            if (($current_time - $last_update_time) < $update_delay_seconds) {
                $message = sprintf(
                    /* translators: %s: Number of seconds to wait */
                    esc_html__('Please wait before updating again. Timeout: %s second(s).', 'exchange-rates'),
                    absint($update_delay_seconds - ($current_time - $last_update_time))
                );
                
                wp_send_json_error($message);
                return;
            }

            try {
                if (!UpdateDataSources::update()) {
                    wp_send_json_error(esc_html__('Unable to update exchange-rate data. Please try again later.', 'exchange-rates'));
                    return;
                }

                wp_send_json_success(esc_html__('Successful updating data.', 'exchange-rates'));
                update_option('exchange_rates_force_data_update_time', $current_time);
            }
            catch (\Exception $e) {
                wp_send_json_error(esc_html__('Unable to update exchange-rate data. Please try again later.', 'exchange-rates'));
            }
        });

        add_action('update_option', function($option_name, $old_value, $new_value) {
            if ($option_name === \Dejurin\ExchangeRates\Models\Settings::$option_name) {
                $old_value = wp_parse_args($old_value, \Dejurin\ExchangeRates\Models\Settings::get_defaults());
                $new_value = wp_parse_args($new_value, \Dejurin\ExchangeRates\Models\Settings::get_defaults());
                if ($old_value['source_id'] !== $new_value['source_id']) {
                    UpdateDataSources::update($new_value['source_id']);
                }
            }
        }, 10, 3);

        add_filter('plugin_action_links_'.plugin_basename($GLOBALS['dejurin_exchange_rates']->plugin_path), ['\Dejurin\ExchangeRates\Admin\Pages\Plugins', 'add_action_links']);
    }
}
