<?php
/**
 * Plugin Name: Exchange Rates synthetic test data (local tests only)
 */

if (!defined('ABSPATH') || '1' !== getenv('EXCHANGE_RATES_TEST_MODE') || 'local' !== wp_get_environment_type()) {
    return;
}

// The isolated fixture cannot answer update checks against WordPress.org.
foreach (array('_maybe_update_core', '_maybe_update_plugins', '_maybe_update_themes') as $callback) {
    remove_action('admin_init', $callback);
}

add_filter('pre_http_request', function ($preempt, $args, $url) {
    if (!in_array($url, array('https://api-bank.fex.to/rates/currencyrate.json', 'https://api-bank.fex.to/sources.json'), true)) {
        return new WP_Error('test_network_disabled', 'Outbound WordPress HTTP requests are disabled in this test environment.');
    }

    $payload = array(
        'source' => 'currencyrate',
        'base' => 'USD',
        'data' => array(
            array('local_time' => '2026-01-02 12:00:00', 'put_time' => '2026-01-02 12:01:00',
                'rates' => array('USD' => 1, 'EUR' => 1.25, 'GBP' => 1.5, 'AUD' => 0.75, 'JPY' => 0.01, 'BRL' => 0.2)),
            array('local_time' => '2026-01-01 12:00:00', 'put_time' => '2026-01-01 12:01:00',
                'rates' => array('USD' => 1, 'EUR' => 1.2, 'GBP' => 1.4, 'AUD' => 0.7, 'JPY' => 0.009, 'BRL' => 0.19)),
        ),
    );

    if ('https://api-bank.fex.to/sources.json' === $url) {
        $payload = array(array('source' => 'currencyrate', 'country' => 'currencyrate', 'base' => 'USD', 'lastupdate' => 1767355200));
    }

    return array('headers' => array('content-type' => 'application/json'), 'body' => wp_json_encode($payload),
        'response' => array('code' => 200, 'message' => 'OK'), 'cookies' => array());
}, 10, 3);
