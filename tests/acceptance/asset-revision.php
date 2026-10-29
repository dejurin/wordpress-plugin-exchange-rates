<?php
/** Local candidate assets only; never loaded by the installable plugin. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) { return; }
// Same-version candidate ZIPs need distinct asset URLs in this disposable review.
$er_acceptance_assets = array_merge(
    glob( WP_PLUGIN_DIR . '/exchange-rates/assets/js/public/*.js' ),
    glob( WP_PLUGIN_DIR . '/exchange-rates/assets/css/public/*.css' )
);
sort( $er_acceptance_assets );
$er_acceptance_hashes = array();
foreach ( $er_acceptance_assets as $er_acceptance_asset ) {
    $er_acceptance_hashes[] = $er_acceptance_asset . ':' . hash_file( 'sha256', $er_acceptance_asset );
}
$er_table_fit_revision = substr( hash( 'sha256', implode( '|', $er_acceptance_hashes ) ), 0, 12 );
foreach ( array( 'script_loader_src', 'style_loader_src' ) as $er_table_fit_filter ) {
	add_filter( $er_table_fit_filter, function ( $url ) use ( $er_table_fit_revision ) {
		return false !== strpos( $url, '/plugins/exchange-rates/' ) ? add_query_arg( 'er_acceptance_build', $er_table_fit_revision, $url ) : $url;
	}, 100 );
}
