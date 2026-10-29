<?php
/** Read-only, synthetic component reproduction on an explicitly isolated WordPress. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'EXCHANGE_RATES_TEST_MODE' ) || 'local' !== wp_get_environment_type() ) {
	return;
}
require_once '/opt/tests/acceptance/asset-revision.php';
require_once '/opt/tests/acceptance/public-native.php';
if ( empty( $_GET['er_public_case'] ) ) { return; }
$er_public_id = sanitize_key( wp_unslash( $_GET['er_public_case'] ) );
if ( ! in_array( $er_public_id, array( 'katuru', 'todayafrica', 'editorialrn', 'morningtelegraph', 'centralbank', 'sovereign' ), true ) ) {
	return;
}
$er_public_case = json_decode( file_get_contents( '/opt/tests/acceptance/public-cases/' . $er_public_id . '.json' ), true );
if ( ! is_array( $er_public_case ) ) {
	wp_die( 'Missing public composition fixture.' );
}
$er_public_size = isset( $_GET['er_public_size'] ) ? (string) absint( $_GET['er_public_size'] ) : '1280';
if ( isset( $er_public_case['environments'][ $er_public_size ]['rates'] ) ) {
	$er_public_case['rates'] = $er_public_case['environments'][ $er_public_size ]['rates'];
	$er_public_case['previous'] = $er_public_case['rates'];
}
add_filter( 'pre_http_request', function () { return new WP_Error( 'fixture_offline', 'The public composition fixture does not fetch rates.' ); }, 0 );
add_filter( 'pre_option_blog_public', '__return_zero' );
add_filter( 'pre_option_exchange-rates', function () use ( $er_public_case ) {
	return array_merge( \Dejurin\ExchangeRates\Models\Settings::get_defaults(), $er_public_case['settings'], array( 'source_id' => $er_public_case['source'], 'base_currency' => $er_public_case['base'], 'rates_available' => true, 'update_timestamp' => time() ) );
}, 999 );
add_filter( 'pre_option_exchange-rates_rates', function () use ( $er_public_case ) {
	return array( 'source' => $er_public_case['source'], 'base' => $er_public_case['base'], 'data' => array(
		array( 'local_time' => '2026-10-28 11:50:00', 'put_time' => '2026-10-28 11:51:00', 'rates' => $er_public_case['rates'] ),
		array( 'local_time' => '2026-10-27 11:50:00', 'put_time' => '2026-10-27 11:51:00', 'rates' => $er_public_case['previous'] ),
	) );
}, 999 );

add_action( 'template_redirect', function () use ( $er_public_id, $er_public_case ) {
	$size = isset( $_GET['er_public_size'] ) ? (string) absint( $_GET['er_public_size'] ) : '1280';
	if ( ! isset( $er_public_case['environments'][ $size ] ) ) {
		wp_die( 'This public viewport has not been observed.', '', array( 'response' => 400 ) );
	}
	$env = $er_public_case['environments'][ $size ];
	$background = '#fff';
	foreach ( $env['ancestors'] as $node ) {
		if ( 'rgba(0, 0, 0, 0)' !== $node['computed']['background-color'] ) {
			$background = $node['computed']['background-color'];
			break;
		}
	}
	$attrs = $er_public_case['attributes'];
	ob_start();
	if ( 'badge' === $er_public_case['surface'] ) {
		$shortcode = '[exchange-rates_badge';
		foreach ( $attrs as $key => $value ) {
			if ( is_array( $value ) ) { $value = implode( ',', $value ); }
			if ( is_bool( $value ) ) { $value = $value ? 'true' : 'false'; }
			$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}
		echo do_shortcode( $shortcode . ']' );
	} else {
		$class = 'table' === $er_public_case['surface'] ? '\\Dejurin\\ExchangeRates\\Widgets\\Table' : '\\Dejurin\\ExchangeRates\\Widgets\\Converter';
		the_widget( $class, $attrs, array( 'before_widget' => '', 'after_widget' => '' ) );
	}
	$widget = ob_get_clean();
	\Dejurin\ExchangeRates\Plugin::register_public_script_style();
	$ancestors = array_reverse( $env['ancestors'] );
	$body_class = '';
	foreach ( $ancestors as $node ) { if ( 'BODY' === $node['tag'] ) { $body_class = $node['class']; } }
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Isolated public composition</title>';
	echo '<script>window.erPublicErrors=[];addEventListener("error",function(e){erPublicErrors.push(e.message)});</script>';
	$failures = array( 'missing' => 'HTMLDialogElement.prototype.showModal=undefined;', 'throws' => 'HTMLDialogElement.prototype.showModal=function(){throw new Error("Simulated modal failure")};' );
	if ( isset( $_GET['er_public_failure'], $failures[ $_GET['er_public_failure'] ] ) ) {
		echo '<script>' . $failures[ $_GET['er_public_failure'] ] . '</script>';
	}
	wp_print_styles( array( 'plugin-exchange-rates-widgets', 'plugin-exchange-rates-footer' ) );
	echo '<style>';
	$css = file_get_contents( '/opt/tests/acceptance/public-cases/' . $er_public_id . '.css' );
	$fonts = json_decode( file_get_contents( '/er-public-fonts/manifest.json' ), true );
	if ( ! is_array( $fonts ) ) { wp_die( 'Missing frozen font manifest.' ); }
	foreach ( $fonts as $url => $font ) {
		if ( ! preg_match( '/^[a-f0-9]{64}\.(woff2|ttf)$/', $font['file'] ) ) { wp_die( 'Invalid font artifact.' ); }
		$bytes = file_get_contents( '/er-public-fonts/' . $font['file'] );
		if ( hash( 'sha256', $bytes ) !== $font['sha256'] ) { wp_die( 'Font checksum mismatch.' ); }
		$mime = '.ttf' === substr( $font['file'], -4 ) ? 'font/ttf' : 'font/woff2';
		$css = str_replace( $url, 'data:' . $mime . ';base64,' . base64_encode( $bytes ), $css );
	}
	echo $css;
	// Only the surrounding layout is normalized; plugin descendants keep their own CSS.
	echo 'html,body{min-width:0!important;margin:0!important;padding:0!important}body{padding:16px!important}.er-public-ancestor{display:contents!important}.er-public-host{display:block!important;float:none!important;position:relative!important;transform:none!important;margin:0!important;max-width:none!important;min-width:0!important;height:auto!important;}';
	echo '</style></head><body style="background-color:' . esc_attr( $background ) . '!important" class="' . esc_attr( $body_class ) . '" data-er-public-case="' . esc_attr( $er_public_id ) . '">';
	$tags = array();
	$inherit = array( 'font-family', 'font-size', 'font-weight', 'font-style', 'line-height', 'letter-spacing', 'color', 'text-transform', 'text-align', 'white-space' );
	$layout = array( 'box-sizing', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'border-top', 'border-right', 'border-bottom', 'border-left', 'background-color' );
	foreach ( $ancestors as $index => $node ) {
		if ( in_array( $node['tag'], array( 'HTML', 'BODY' ), true ) ) { continue; }
		$tag = strtolower( $node['tag'] );
		if ( ! in_array( $tag, array( 'div', 'section', 'aside', 'main', 'footer', 'article', 'p' ), true ) ) { $tag = 'div'; }
		$host = $index === count( $ancestors ) - 1;
		$style = '';
		foreach ( array_merge( $inherit, $host ? $layout : array() ) as $property ) {
			$value = $node['computed'][ $property ];
			// These themes use unitless leading. Preserve scaling in larger child text.
			if ( 'line-height' === $property && floatval( $node['computed']['font-size'] ) > 0 && 'normal' !== $value ) {
				$value = floatval( $value ) / floatval( $node['computed']['font-size'] );
			}
			$style .= $property . ':' . $value . '!important;';
		}
		if ( $host ) { $style .= 'width:' . $node['computed']['width'] . '!important;'; }
		echo '<' . $tag . ( '' !== $node['id'] ? ' id="' . esc_attr( $node['id'] ) . '"' : '' ) . ' class="' . esc_attr( $node['class'] . ( $host ? ' er-public-host' : ' er-public-ancestor' ) ) . '" style="' . esc_attr( $style ) . '">';
		$tags[] = $tag;
	}
	echo $widget;
	echo '<div data-er-public-neighbor style="height:24px;clear:both;font:12px sans-serif">Following content</div>';
	foreach ( array_reverse( $tags ) as $tag ) { echo '</' . $tag . '>'; }
	do_action( 'wp_footer' );
	if ( isset( $_GET['er_public_native_case'] ) ) {
		echo '<script>window.erPublicMeasure=';
		readfile( '/opt/tests/acceptance/public-measure.js' );
		echo ';';
		readfile( '/opt/tests/acceptance/public-native-case.js' );
		echo '</script>';
	}
	if ( isset( $_GET['er_public_measure'] ) ) {
		echo '<script>window.addEventListener("load",async function(){await document.fonts.ready;const measure=';
		readfile( '/opt/tests/acceptance/public-measure.js' );
		echo ';parent.postMessage({type:"er-public-measure",id:' . wp_json_encode( $er_public_id ) . ',size:' . wp_json_encode( $size ) . ',result:measure()},"http://127.0.0.1:19330")});</script>';
	}
	echo '</body></html>';
	exit;
}, -999 );
