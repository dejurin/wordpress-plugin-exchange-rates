<?php
/** Same-version footer assets must invalidate isolated browser acceptance URLs. */
define( 'ABSPATH', __DIR__ );
define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/er-asset-revision-' . uniqid( '', true ) );
putenv( 'EXCHANGE_RATES_TEST_MODE=1' );
function wp_get_environment_type() { return 'local'; }
function add_filter( $name, $callback, $priority ) { $GLOBALS['asset_filters'][ $name ] = $callback; }
function add_query_arg( $name, $value, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $name . '=' . $value; }
$js = WP_PLUGIN_DIR . '/exchange-rates/assets/js/public';
$css = WP_PLUGIN_DIR . '/exchange-rates/assets/css/public';
mkdir( $js, 0700, true ); mkdir( $css, 0700, true );
register_shutdown_function( function () use ( $js, $css ) {
    foreach ( array( $js, $css ) as $dir ) {
        foreach ( glob( $dir . '/*' ) as $path ) { unlink( $path ); }
        rmdir( $dir ); rmdir( dirname( $dir ) );
    }
    rmdir( WP_PLUGIN_DIR . '/exchange-rates/assets' );
    rmdir( WP_PLUGIN_DIR . '/exchange-rates' ); rmdir( WP_PLUGIN_DIR );
} );
file_put_contents( $js . '/common.js', 'common' );
file_put_contents( $css . '/style.css', 'style' );
function acceptance_url( $url ) {
    require __DIR__ . '/acceptance/asset-revision.php';
    return call_user_func( $GLOBALS['asset_filters']['script_loader_src'], $url );
}
$url = 'http://127.0.0.1/wp-content/plugins/exchange-rates/assets/js/public/footer.js?ver=1.5.0';
$old = acceptance_url( $url );
file_put_contents( $js . '/footer.js', 'footer-v1' );
$added = acceptance_url( $url );
file_put_contents( $js . '/footer.js', 'footer-v2' );
$updated = acceptance_url( $url );
file_put_contents( $css . '/footer.css', 'footer-css' );
$styled = acceptance_url( $url );
$checks = array(
    'adding footer script changes revision' => $old !== $added,
    'editing only footer script changes revision' => $added !== $updated,
    'adding footer CSS changes revision' => $updated !== $styled,
    'unchanged files keep stable revision' => $styled === acceptance_url( $url ),
    'unrelated URLs stay intact' => 'https://example.test/theme.js' === acceptance_url( 'https://example.test/theme.js' ),
);
foreach ( $checks as $name => $passed ) { echo ( $passed ? 'PASS: ' : 'FAIL: ' ) . $name . "\n"; }
exit( in_array( false, $checks, true ) ? 1 : 0 );
