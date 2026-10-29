<?php
/** Native ZIP replacement and raw saved-data checks in an owned disposable site. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() || ! getenv( 'ER_ACCEPTANCE_ID' ) ) {
	throw new RuntimeException( 'Native acceptance requires a marked disposable local site.' );
}
$host = wp_parse_url( home_url(), PHP_URL_HOST );
if ( ! in_array( $host, array( '127.0.0.1', 'localhost' ), true ) ) {
	WP_CLI::error( 'Only loopback acceptance sites are allowed.' );
}
$mode = isset( $args[0] ) ? $args[0] : '';
$label = isset( $args[1] ) ? $args[1] : '';
$state_option = getenv( 'ER_ACCEPTANCE_STATE_OPTION' ) ? getenv( 'ER_ACCEPTANCE_STATE_OPTION' ) : 'er_acceptance_saved_state';
if ( ! preg_match( '/^er_acceptance_saved_state(?:_[a-z0-9]+)?$/', $state_option ) ) { WP_CLI::error( 'Invalid snapshot option.' ); }
if ( ! in_array( $mode, array( 'install', 'seed', 'snapshot', 'upgrade', 'verify' ), true ) || ! preg_match( '/^[a-z0-9][a-z0-9.-]*$/', $label ) ) {
	WP_CLI::error( 'Usage: native-upgrade.php install|seed|snapshot|upgrade|verify PACKAGE_BASENAME' );
}
$package_dir = getenv( 'ER_ACCEPTANCE_PACKAGES' ) ? realpath( getenv( 'ER_ACCEPTANCE_PACKAGES' ) ) : '/acceptance/packages';
$package = $package_dir . '/' . $label . '.zip';
if ( ! $package_dir || ! is_dir( $package_dir ) || ! is_file( $package ) || is_link( $package ) || dirname( realpath( $package ) ) !== $package_dir ) {
	WP_CLI::error( 'A reviewed local package is required.' );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
$zip = new PclZip( $package );
$entries = $zip->extract( PCLZIP_OPT_EXTRACT_AS_STRING );
if ( ! is_array( $entries ) ) { WP_CLI::error( 'Cannot read the package.' ); }
$expected = array();
foreach ( $entries as $entry ) {
	if ( $entry['folder'] ) { continue; }
	$name = $entry['filename'];
	if ( 0 !== strpos( $name, 'exchange-rates/' ) || false !== strpos( $name, '..' ) || false !== strpos( $name, '\\' ) || isset( $expected[ $name ] ) ) {
		WP_CLI::error( 'Unsafe or duplicate ZIP entry.' );
	}
	$expected[ $name ] = hash( 'sha256', $entry['content'] );
	if ( 'exchange-rates/Install.php' === $name ) {
		preg_match( '/^Version:\s*(\d+\.\d+\.\d+)\s*$/m', $entry['content'], $match );
		$version = isset( $match[1] ) ? $match[1] : '';
	}
}
if ( empty( $version ) || ! isset( $expected['exchange-rates/src/Plugin.php'] ) ) { WP_CLI::error( 'Invalid plugin package.' ); }
$plugin = 'exchange-rates/Install.php';
add_filter( 'pre_http_request', function ( $response, $request, $url ) {
	$parts = wp_parse_url( $url );
	if ( isset( $parts['host'], $parts['path'] ) && 'api.wordpress.org' === $parts['host'] && in_array( $parts['path'], array( '/plugins/update-check/1.1/', '/themes/update-check/1.1/', '/core/version-check/1.7/' ), true ) ) {
		return array( 'headers' => array(), 'body' => wp_json_encode( array( 'plugins' => array(), 'themes' => array(), 'offers' => array(), 'no_update' => array(), 'translations' => array() ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
	}
	return $response;
}, 1000, 3 );
require_once __DIR__ . '/native-state.php';
if ( 'snapshot' === $mode ) {
	if ( 'er_acceptance_saved_state' === $state_option || get_option( $state_option ) || ! is_plugin_active( $plugin ) || \Dejurin\ExchangeRates\Plugin::VERSION !== $version ) { WP_CLI::error( 'Snapshot requires a new run key and the active reviewed baseline.' ); }
	update_option( $state_option, array( 'owner' => getenv( 'ER_ACCEPTANCE_ID' ), 'state' => er_acceptance_raw_state() ) );
	WP_CLI::success( 'Captured existing configuration for this run; no reseeding.' );
	return;
}
if ( 'seed' === $mode ) {
	if ( get_option( $state_option ) ) { WP_CLI::error( 'Refuse to reseed: this would invalidate upgrade evidence.' ); }
	$settings = \Dejurin\ExchangeRates\Models\Settings::get_defaults();
	$settings['source_id'] = 'currencyrate'; $settings['currency_format'] = 3; $settings['decimals'] = 2;
	update_option( 'exchange-rates', $settings );
	if ( ! \Dejurin\ExchangeRates\Service\UpdateDataSources::update() ) { WP_CLI::error( 'Synthetic rates could not be seeded.' ); }
	update_option( 'widget_exchange-rates_currency-table', array( 2 => array( 'title' => 'Saved table', 'base_currency' => 'USD', 'currency_list' => array( 'EUR', 'GBP' ), 'amount' => 25, 'code' => true, 'flag_type' => 'none', 'amount_active' => true ), '_multiwidget' => 1 ) );
	update_option( 'widget_exchange-rates_currency-converter', array( 3 => array( 'title' => 'Saved converter', 'from' => 'USD', 'to' => 'EUR', 'amount' => 35.25 ), '_multiwidget' => 1 ) );
	update_option( 'sidebars_widgets', array( 'wp_inactive_widgets' => array(), 'sidebar-1' => array( 'exchange-rates_currency-table-2', 'exchange-rates_currency-converter-3' ), 'array_version' => 3 ) );
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'acceptance-saved-shortcodes', 'post_title' => 'Synthetic saved configurations', 'post_content' => '[exchange-rates_currency-table base_currency="USD" currency_list="EUR,GBP" amount="25" amount_active="true"] [exchange-rates_currency-converter title="Saved converter" from="USD" to="EUR" amount="25"] [exchange-rates_badge base_currency="USD" currency_list="EUR,GBP" amount="25"]' ), true );
	if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
	update_option( $state_option, array( 'owner' => getenv( 'ER_ACCEPTANCE_ID' ), 'state' => er_acceptance_raw_state() ) );
	WP_CLI::success( 'Saved synthetic options, rates, widgets, sidebars and pages before replacement.' );
	return;
}
if ( in_array( $mode, array( 'install', 'upgrade' ), true ) ) {
	$skin = new WP_Ajax_Upgrader_Skin(); $upgrader = new Plugin_Upgrader( $skin );
	if ( 'install' === $mode ) {
		if ( is_file( WP_PLUGIN_DIR . '/' . $plugin ) ) { WP_CLI::error( 'Fresh install requires an absent plugin.' ); }
		$result = $upgrader->install( $package );
		if ( true !== $result ) { WP_CLI::error( 'Native fresh installation failed: ' . $skin->get_errors()->get_error_message() ); }
	} else {
		$saved = get_option( $state_option );
		if ( ! is_array( $saved ) || $saved['owner'] !== getenv( 'ER_ACCEPTANCE_ID' ) || ! is_plugin_active( $plugin ) ) { WP_CLI::error( 'Upgrade requires the active, owned seeded fixture.' ); }
		$previous = get_site_transient( 'update_plugins' );
		$update = (object) array( 'last_checked' => time(), 'checked' => array( $plugin => \Dejurin\ExchangeRates\Plugin::VERSION ), 'response' => array( $plugin => (object) array( 'slug' => 'exchange-rates', 'plugin' => $plugin, 'new_version' => $version, 'package' => $package, 'url' => 'https://wordpress.org/plugins/exchange-rates/' ) ) );
		set_site_transient( 'update_plugins', $update );
		try {
			$result = $upgrader->bulk_upgrade( array( $plugin ) );
			if ( ! is_array( $result ) || empty( $result[ $plugin ] ) || is_wp_error( $result[ $plugin ] ) || ! is_array( $result[ $plugin ] ) ) { throw new RuntimeException( 'Native bulk update failed: ' . $skin->get_errors()->get_error_message() ); }
		} finally {
			if ( false === $previous ) { delete_site_transient( 'update_plugins' ); } else { set_site_transient( 'update_plugins', $previous ); }
		}
		if ( ! is_plugin_active( $plugin ) ) { WP_CLI::error( 'Updater changed activation state.' ); }
	}
	WP_CLI::success( 'Native ' . $mode . ' completed for ' . $label . ' (' . $version . '); verify in a fresh process.' );
	return;
}
$saved = get_option( $state_option );
if ( ! is_array( $saved ) || $saved['owner'] !== getenv( 'ER_ACCEPTANCE_ID' ) || $saved['state'] !== er_acceptance_raw_state() ) { WP_CLI::error( 'Raw saved state differs or ownership is missing.' ); }
if ( ! is_plugin_active( $plugin ) || \Dejurin\ExchangeRates\Plugin::VERSION !== $version ) { WP_CLI::error( 'Activation/runtime version differs.' ); }
$actual = array();
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WP_PLUGIN_DIR . '/exchange-rates', FilesystemIterator::SKIP_DOTS ) ) as $file ) {
	if ( $file->isFile() ) { $actual[ 'exchange-rates/' . substr( $file->getPathname(), strlen( WP_PLUGIN_DIR . '/exchange-rates/' ) ) ] = hash_file( 'sha256', $file->getPathname() ); }
}
ksort( $expected ); ksort( $actual );
if ( $actual !== $expected ) { WP_CLI::error( 'Installed file inventory differs from the reviewed ZIP.' ); }
$page = get_page_by_path( 'acceptance-saved-shortcodes' );
$html = do_shortcode( $page->post_content );
foreach ( array( 'currency-table', 'currency-converter', 'badge-leaders' ) as $marker ) {
	if ( false === strpos( $html, $marker ) ) { WP_CLI::error( 'Saved shortcode failed to render: ' . $marker ); }
}
foreach ( array( 'Table' => array( 'widget_exchange-rates_currency-table', 2 ), 'Converter' => array( 'widget_exchange-rates_currency-converter', 3 ) ) as $type => $widget ) {
	$instances = get_option( $widget[0] ); ob_start(); the_widget( '\\Dejurin\\ExchangeRates\\Widgets\\' . $type, $instances[ $widget[1] ] ); $html = ob_get_clean();
	if ( false === strpos( $html, 'cr-exchange-rates' ) ) { WP_CLI::error( 'Saved native widget failed to render.' ); }
}
WP_CLI::success( 'Verified ' . $label . ': ' . count( $actual ) . ' file hashes, activation, runtime, saved renderers; raw state SHA256=' . hash( 'sha256', serialize( $saved['state'] ) ) );
