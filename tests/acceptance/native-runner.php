<?php
/** Plugin Name: Disposable native browser acceptance runner */
if (!defined('ABSPATH') || '1' !== getenv('EXCHANGE_RATES_TEST_MODE') || 'local' !== wp_get_environment_type()) {
    return;
}
add_action('template_redirect', function () {
    if (empty($_GET['er-native-acceptance'])) {
        return;
    }
    if (!in_array(parse_url(home_url(), PHP_URL_HOST), array('localhost', '127.0.0.1'), true)) {
        wp_die('Loopback only', '', array('response' => 403));
    }
    nocache_headers();
    if ('POST' === $_SERVER['REQUEST_METHOD']) {
        if (empty($_SERVER['HTTP_X_ACCEPTANCE_NONCE']) || !wp_verify_nonce($_SERVER['HTTP_X_ACCEPTANCE_NONCE'], 'er-native-acceptance')) {
            wp_send_json_error('Invalid acceptance nonce', 403);
        }
        $raw = file_get_contents('php://input', false, null, 0, 262145);
        $report = json_decode($raw, true);
        if (strlen($raw) > 262144 || !is_array($report) || !isset($report['browser'], $report['cases']) || !in_array($report['browser'], array('safari', 'firefox'), true) || !is_array($report['cases'])) {
            wp_send_json_error('Invalid report', 400);
        }
        $dir = WP_CONTENT_DIR . '/native-acceptance' . ('date-labels' === $_GET['er-native-acceptance'] ? '/date-labels' : '');
        if (!wp_mkdir_p($dir) || false === file_put_contents($dir . '/' . $report['browser'] . '.json', wp_json_encode($report), LOCK_EX)) {
            wp_send_json_error('Cannot save report', 500);
        }
        wp_send_json_success();
    }
    $html = file_get_contents('/opt/tests/acceptance/' . ('date-labels' === $_GET['er-native-acceptance'] ? 'date-labels.html' : 'native-runner.html'));
    echo str_replace('__ACCEPTANCE_NONCE__', esc_attr(wp_create_nonce('er-native-acceptance')), $html);
    exit;
});

// Failure injection belongs to this disposable harness, never the shipped plugin.
add_action('wp_head', function () {
    echo '<script>window.erNativeErrors=[];addEventListener("error",function(event){erNativeErrors.push(event.message)});</script>';
    if (empty($_GET['dialog_test'])) {
        return;
    }
    $modes = array(
        'missing-api' => 'HTMLDialogElement.prototype.showModal = undefined;',
        'no-constructor' => 'window.HTMLDialogElement = undefined;',
        'throws' => 'HTMLDialogElement.prototype.showModal = function(){throw new Error("simulated failure")};',
        'nonmodal' => 'HTMLDialogElement.prototype.showModal = function(){this.setAttribute("open", "")};'
    );
    if (isset($modes[$_GET['dialog_test']])) {
        echo '<script>' . $modes[$_GET['dialog_test']] . '</script>';
    }
}, -100);
