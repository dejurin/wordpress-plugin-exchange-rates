<?php
/** Separate post-round-trip activation and cron checks. Never reseed upgrade data. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ER_LOCALWP_OWNER' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() || ER_LOCALWP_OWNER !== getenv( 'ER_ACCEPTANCE_ID' ) ) {
	throw new RuntimeException( 'Owned LocalWP acceptance site required.' );
}
if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { WP_CLI::error( 'Loopback only.' ); }
require_once __DIR__ . '/native-state.php';
$snapshot_option = 'er_localwp_lifecycle_before' . ( defined( 'ER_LOCALWP_TEST_RUN' ) ? '_' . ER_LOCALWP_TEST_RUN : '' );
$mode = isset( $args[0] ) ? $args[0] : '';
if ( ! in_array( $mode, array( 'snapshot', 'inactive', 'active', 'cron' ), true ) ) { WP_CLI::error( 'Invalid lifecycle mode.' ); }
if ( 'snapshot' === $mode ) {
	if ( get_option( $snapshot_option ) ) { WP_CLI::error( 'Refuse to overwrite lifecycle evidence.' ); }
	update_option( $snapshot_option, array( 'state' => er_acceptance_raw_state(), 'started' => time() ) );
	WP_CLI::success( 'Lifecycle snapshot saved after completed upgrade/rollback.' );
	return;
}
$before = get_option( $snapshot_option );
if ( ! $before ) { WP_CLI::error( 'Missing lifecycle snapshot.' ); }
$expected = $before['state']; $actual = er_acceptance_raw_state();
$old_settings = unserialize( $expected['options']['exchange-rates'] );
$new_settings = unserialize( $actual['options']['exchange-rates'] );
$old_time = $old_settings['update_timestamp']; $new_time = $new_settings['update_timestamp'];
if ( 'inactive' !== $mode ) {
	unset( $old_settings['update_timestamp'], $new_settings['update_timestamp'] );
	$expected['options']['exchange-rates'] = serialize( $old_settings );
	$actual['options']['exchange-rates'] = serialize( $new_settings );
	if ( $new_time < $before['started'] ) { WP_CLI::error( 'Refresh did not save a current successful timestamp.' ); }
}
if ( $expected !== $actual ) { WP_CLI::error( 'Lifecycle changed saved choices, rates, widgets, sidebars or content.' ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'exchange-rates/Install.php' ) !== ( 'inactive' !== $mode ) ) { WP_CLI::error( 'Wrong activation state.' ); }
$recurring = 0; $retry = 0;
foreach ( _get_cron_array() as $hooks ) {
	if ( isset( $hooks['exchange-rates_data_update'] ) ) { $recurring += count( $hooks['exchange-rates_data_update'] ); }
	if ( isset( $hooks['exchange-rates_data_retry'] ) ) { $retry += count( $hooks['exchange-rates_data_retry'] ); }
}
if ( $recurring !== ( 'inactive' === $mode ? 0 : 1 ) || 0 !== $retry ) { WP_CLI::error( 'Unexpected recurring/retry event count.' ); }
WP_CLI::success( wp_json_encode( array( 'mode' => $mode, 'saved_choices_preserved' => true, 'recurring' => $recurring, 'retry' => $retry, 'previous_update_timestamp' => $old_time, 'current_update_timestamp' => $new_time ) ) );
