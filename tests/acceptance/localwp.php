<?php
/** Disposable LocalWP harness: render saved configuration without option filters. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'ER_LOCALWP_TEST_ROOT' ) || ! defined( 'ER_LOCALWP_OWNER' ) || ! defined( 'ER_LOCALWP_TEST_PACKAGES' ) || 'local' !== wp_get_environment_type() ) {
	return;
}
if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) {
	return;
}
if ( defined( 'ER_LOCALWP_TEST_RUN' ) ) {
    if ( ! preg_match( '/^[a-z0-9]+$/', ER_LOCALWP_TEST_RUN ) ) { throw new RuntimeException( 'Invalid isolated run ID.' ); }
    putenv( 'ER_ACCEPTANCE_STATE_OPTION=er_acceptance_saved_state_' . ER_LOCALWP_TEST_RUN );
}
putenv( 'EXCHANGE_RATES_TEST_MODE=1' );
putenv( 'ER_ACCEPTANCE_ID=' . ER_LOCALWP_OWNER );
putenv( 'ER_ACCEPTANCE_PACKAGES=' . ER_LOCALWP_TEST_PACKAGES );
require_once __DIR__ . '/asset-revision.php';
require ER_LOCALWP_TEST_ROOT . '/tests/http-fixture.php';

add_action( 'widgets_init', function () {
	register_sidebar( array( 'name' => 'Acceptance saved widgets', 'id' => 'sidebar-1', 'before_widget' => '<section>', 'after_widget' => '</section>' ) );
} );
add_action( 'template_redirect', function () {
	if ( empty( $_GET['er_localwp'] ) ) { return; }
	nocache_headers();
	if ( 'runner' === $_GET['er_localwp'] ) {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			if ( empty( $_SERVER['HTTP_X_ACCEPTANCE_NONCE'] ) || ! wp_verify_nonce( $_SERVER['HTTP_X_ACCEPTANCE_NONCE'], 'er-localwp' ) ) { wp_send_json_error( 'Invalid nonce', 403 ); }
			$raw = file_get_contents( 'php://input', false, null, 0, 262145 );
			$report = json_decode( $raw, true );
			if ( strlen( $raw ) > 262144 || ! is_array( $report ) || ! isset( $report['stage'], $report['browser'], $report['cases'] ) || ! in_array( $report['stage'], array( 'baseline', 'candidate', 'rollback', 'restored' ), true ) || ! in_array( $report['browser'], array( 'chromium', 'safari', 'firefox' ), true ) ) { wp_send_json_error( 'Invalid report', 400 ); }
			$dir = WP_CONTENT_DIR . '/localwp-acceptance';
			if ( ! wp_mkdir_p( $dir ) || false === file_put_contents( $dir . '/' . $report['stage'] . '-' . $report['browser'] . '.json', wp_json_encode( $report ), LOCK_EX ) ) { wp_send_json_error( 'Cannot save report', 500 ); }
			wp_send_json_success();
		}
		echo str_replace( '__NONCE__', esc_attr( wp_create_nonce( 'er-localwp' ) ), file_get_contents( __DIR__ . '/localwp.html' ) );
		exit;
	}
	$surface = isset( $_GET['surface'] ) ? $_GET['surface'] : 'table';
	if ( ! in_array( $surface, array( 'table', 'converter', 'badge', 'native-table', 'native-converter', 'sidebar' ), true ) ) { wp_die( 'Unknown surface' ); }
	$mode = isset( $_GET['failure'] ) ? $_GET['failure'] : '';
	$failures = array( 'missing' => 'HTMLDialogElement.prototype.showModal=undefined;', 'throws' => 'HTMLDialogElement.prototype.showModal=function(){throw Error("simulated")};', 'nonmodal' => 'HTMLDialogElement.prototype.showModal=function(){this.setAttribute("open","")};' );
	?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LocalWP saved configuration</title>
	<script>window.erLocalErrors=[];addEventListener('error',function(e){erLocalErrors.push(e.message)});</script>
	<?php if ( isset( $failures[ $mode ] ) ) { echo '<script>' . $failures[ $mode ] . '</script>'; } ?>
	<?php wp_head(); ?>
	<style>body{margin:0;background:#fff;color:#30343b;font:16px/1.5 system-ui}main{max-width:960px;padding:20px;margin:auto;box-sizing:border-box}h1{font-size:20px}#localwp-fixture{max-width:100%}.context{color:#667085;font-size:12px}</style></head><body><main>
	<p class="context">Isolated LocalWP · saved database configuration · synthetic rates</p><h1><?php echo esc_html( $surface ); ?></h1><section id="localwp-fixture">
	<?php
	if ( 'sidebar' === $surface ) {
		dynamic_sidebar( 'sidebar-1' );
	} elseif ( 0 === strpos( $surface, 'native-' ) ) {
		$table = 'native-table' === $surface;
		$options = get_option( $table ? 'widget_exchange-rates_currency-table' : 'widget_exchange-rates_currency-converter' );
		the_widget( '\\Dejurin\\ExchangeRates\\Widgets\\' . ( $table ? 'Table' : 'Converter' ), $options[ $table ? 2 : 3 ] );
	} else {
		$page = get_page_by_path( 'acceptance-saved-shortcodes' );
		preg_match_all( '/\[exchange-rates_[^\]]+\]/', $page->post_content, $matches );
		$indexes = array( 'table' => 0, 'converter' => 1, 'badge' => 2 );
		echo do_shortcode( $matches[0][ $indexes[ $surface ] ] );
	}
	?></section></main><?php wp_footer(); ?></body></html><?php exit;
} );
