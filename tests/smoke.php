<?php
/** Run through WP-CLI only in the isolated test environment. */

if (!defined('WP_CLI') || !WP_CLI || '1' !== getenv('EXCHANGE_RATES_TEST_MODE') || 'local' !== wp_get_environment_type()) {
    throw new RuntimeException('Smoke checks require the isolated local test environment.');
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR);

function exchange_rates_check($condition, $message)
{
    if (!$condition) {
        WP_CLI::error($message);
    }
    WP_CLI::log('PASS: '.$message);
}

require_once ABSPATH.'wp-admin/includes/plugin.php';
$metadata = get_plugin_data(WP_PLUGIN_DIR.'/exchange-rates/Install.php', false, false);
exchange_rates_check(is_plugin_active('exchange-rates/Install.php'), 'Plugin activated with the correct filename case');
exchange_rates_check($metadata['Version'] === \Dejurin\ExchangeRates\Plugin::VERSION, 'Autoloader and version agree');
exchange_rates_check(false !== wp_next_scheduled('exchange-rates_data_update'), 'Recurring update hook is registered');
exchange_rates_check(\Dejurin\ExchangeRates\Service\UpdateDataSources::update(), 'Synthetic HTTP response persisted through the normal update path');
exchange_rates_check(\Dejurin\ExchangeRates\Service\RatesData::has_previous_rates(), 'Current and previous data are available');
$sources = \Dejurin\ExchangeRates\Models\DataSources::getInstance()->get_sources_data();
exchange_rates_check($sources['status'] && count($sources['data']) === 1, 'Synthetic source catalog is available');

$shortcodes = array(
    'badge' => '[exchange-rates_badge base_currency="USD" currency_list="EUR,GBP" code="true"]',
    'currency-table' => '[exchange-rates_currency-table base_currency="USD" currency_list="EUR,GBP" code="true"]',
    'currency-converter' => '[exchange-rates_currency-converter from="USD" to="EUR"]',
);
foreach ($shortcodes as $name => $shortcode) {
    $html = do_shortcode($shortcode);
    exchange_rates_check(false !== strpos($html, 'cr-exchange-rates') && false === strpos($html, 'Error:'), 'Shortcode renders: '.$name);
}

global $wp_widget_factory;
foreach (array('Table', 'Converter') as $name) {
    $class = '\\Dejurin\\ExchangeRates\\Widgets\\'.$name;
    exchange_rates_check(isset($wp_widget_factory->widgets[$class]), 'Widget registered: '.$name);
    ob_start();
    the_widget($class, array(), array('before_widget' => '<section>', 'after_widget' => '</section>'));
    $html = ob_get_clean();
    exchange_rates_check(false !== strpos($html, 'cr-exchange-rates') && false === strpos($html, 'Error:'), 'Widget renders: '.$name);
}

$plugin = $GLOBALS['dejurin_exchange_rates'];
$handle = 'plugin-exchange-rates-widgets-settings';
wp_dequeue_style($handle);
$plugin->register_admin_script_style('post.php');
exchange_rates_check(!wp_style_is($handle, 'enqueued'), 'Plugin admin CSS is absent from the post editor');
$plugin->register_admin_script_style('settings_page_exchange-rates-general');
exchange_rates_check(wp_style_is($handle, 'enqueued'), 'Plugin admin CSS is present on its settings page');
wp_set_current_user(1);
\Dejurin\ExchangeRates\Admin\Settings\General\Page::init();
ob_start();
\Dejurin\ExchangeRates\Admin\Settings\General\Page::render();
$settings_html = ob_get_clean();
exchange_rates_check(false !== strpos($settings_html, 'plugin-exchange-rates-admin'), 'Settings page renders');

$existing = get_page_by_path('exchange-rates-smoke');
$page = array(
    'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'exchange-rates-smoke',
    'post_title' => 'Exchange Rates Smoke - synthetic data',
    'post_content' => '<p>Local test fixture. These are synthetic exchange rates.</p>'.implode("\n\n", $shortcodes),
);
if ($existing) {
    $page['ID'] = $existing->ID;
}
$id = wp_insert_post($page, true);
exchange_rates_check(!is_wp_error($id) && $id > 0, 'Synthetic smoke page exists');
WP_CLI::success('Smoke complete. Page: '.get_permalink($id));
