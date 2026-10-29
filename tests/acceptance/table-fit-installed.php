<?php
/** Read-only receipt for the owned disposable package review site. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Disposable test site required.' ); }
require_once __DIR__ . '/../regressions/native-state.php';
$saved = get_option( 'er_acceptance_saved_state' );
$state = er_acceptance_raw_state();
$files = array();
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WP_PLUGIN_DIR . '/exchange-rates', FilesystemIterator::SKIP_DOTS ) ) as $file ) {
    if ( $file->isFile() ) { $files[substr( $file->getPathname(), strlen( WP_PLUGIN_DIR . '/exchange-rates/' ) )] = hash_file( 'sha256', $file->getPathname() ); }
}
ksort( $files );
$diff = array();
if ( is_array( $saved ) ) {
    foreach ( $state['options'] as $key => $value ) { if ( $value !== $saved['state']['options'][$key] ) { $diff[] = $key; } }
    if ( $state['pages'] !== $saved['state']['pages'] ) { $diff[] = 'pages'; }
}
echo wp_json_encode( array( 'version' => \Dejurin\ExchangeRates\Plugin::VERSION, 'files' => $files, 'saved_snapshot_exists' => is_array( $saved ), 'saved_owner_matches' => is_array( $saved ) && $saved['owner'] === getenv( 'ER_ACCEPTANCE_ID' ), 'changed_since_old_snapshot' => $diff, 'current_state_sha256' => hash( 'sha256', serialize( $state ) ) ), JSON_PRETTY_PRINT );
