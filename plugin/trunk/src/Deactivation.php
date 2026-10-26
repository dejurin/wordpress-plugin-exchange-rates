<?php

namespace Dejurin\ExchangeRates;

if (!defined('ABSPATH')) {
    exit;
}

class Deactivation
{
    public static function run()
    {
        // Revoke older requests before removing their queued work.
        if ( ! Service\RefreshLifecycle::cancel() ) {
            wp_die( esc_html__( 'Unable to cancel exchange-rate updates. Please try deactivating the plugin again.', 'exchange-rates' ) );
        }
        // Delete Transient
        delete_transient(Models\DataSources::$transient_option_name);

        // Delete Schedul
        Cron\UpdateRates::clear_tasks();
    }
}
