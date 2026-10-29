<?php
/** Shared public attribution and rate information. */
namespace Dejurin\ExchangeRates\Service;

use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Footer {
	private static $needed = false;
	private static $rendered = false;

	/** API timestamps without an offset are UTC, never the server time zone. */
	private static function timestamp( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?$/', $value ) ) { return null; }
		try {
			$date = new \DateTimeImmutable( $value, new \DateTimeZone( 'UTC' ) );
			$errors = \DateTimeImmutable::getLastErrors();
			if ( $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) { return null; }
			return $date->setTimezone( new \DateTimeZone( 'UTC' ) );
		} catch ( \Exception $error ) {
			return null;
		}
	}

	private static function link( $url, $label, $modal = false, $class = '' ) {
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer noopener' . ( $modal ? ' nofollow' : '' ) . '">' . $label . '<span class="er-a11y-sr-only"> ' . esc_html__( '(opens in a new tab)', 'exchange-rates' ) . '</span></a>';
	}

	/** Preserve the three legacy attribution destinations and the actual data source. */
	public static function caption( $parameters, $time, $source_id, $kind ) {
		if ( ! self::$needed ) {
			self::$needed = true;
			add_action( 'wp_footer', array( __CLASS__, 'render_dialog' ), 5 );
		}
		$sources = Sources::get_list();
		$known = isset( $sources[ $source_id ] );
		$source = $known ? $sources[ $source_id ] : array( 'name' => __( 'Unknown source', 'exchange-rates' ), 'source_url' => '', 'compact' => __( 'Unknown source', 'exchange-rates' ) );
		$external = 'currencyrate' !== $source_id;
		$base = isset( $parameters['base_currency'] ) ? strtoupper( $parameters['base_currency'] ) : 'USD';
		$quote = isset( $parameters['quote_currency'] ) ? strtolower( $parameters['quote_currency'] ) : '';
		$date = self::timestamp( isset( $time['local_time'] ) ? $time['local_time'] : null );
		$saved = self::timestamp( isset( $time['put_time'] ) ? $time['put_time'] : null );
		$marker = $known ? '<span class="er-source-mark" aria-hidden="true" title="' . esc_attr__( 'Source of displayed rates', 'exchange-rates' ) . '"></span>' : '';
		$label = __( 'Data details and disclaimer', 'exchange-rates' );
		$control = '<button type="button" class="' . ( 2 === $kind ? 'er-badge-data-button' : 'er-date' ) . '" data-er-info data-recorded-at="' . esc_attr( $saved ? $saved->format( 'Y-m-d\TH:i:s\Z' ) : '' ) . '" data-source-name="' . esc_attr( $source['name'] ) . '" data-source-url="' . esc_url( $source['source_url'] ) . '" aria-haspopup="dialog" aria-controls="exchange-rates-information" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label . ( $date ? ', ' . $date->format( 'd M Y H:i' ) . ' UTC' : '' ) ) . '"><span class="er-badge-data-time"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span>';
		$control .= $date ? '<time datetime="' . esc_attr( $date->format( 'Y-m-d\TH:i:s\Z' ) ) . '">' . esc_html( $date->format( 'd M H:i' ) . ' UTC' ) . '</time>' : '<span>' . esc_html__( 'Not available', 'exchange-rates' ) . '</span>';
		$control .= '</span></button>';
		$type = 2 === $kind ? 'badge' : ( 1 === $kind ? 'converter' : 'table' );
		$html = '<div class="cr-exchange-rates er-footer-slot er-footer-' . $type . '">';
		$footer_label = esc_attr__( 'Exchange rate information', 'exchange-rates' );
		if ( 2 === $kind ) {
			$html .= '<footer class="er-badge-caption" aria-label="' . $footer_label . '"><span class="er-badge-links"><span class="er-badge-brand">' . self::link( 'https://currencyrate.today/', ( ! $external ? '<span class="er-badge-own-marker">' . $marker . '</span>' : '' ) . 'CurrencyRate', false, 'er-credit-with-marker' ) . '<span class="er-link-separator" aria-hidden="true">:</span></span>' . self::link( 'https://currencyconvert.online/' . strtolower( $base ), esc_html( $base ) ) . '</span><span class="er-badge-data-meta">';
			if ( $external ) { $html .= '<span class="er-badge-data-source" title="' . esc_attr( $source['name'] ) . '"><span class="er-badge-bank-marker">' . $marker . '</span><span class="er-badge-data-source-label">' . esc_html( $source['compact'] ) . '</span></span><span class="er-badge-data-separator" aria-hidden="true">·</span>'; }
			$html .= $control . '</span></footer>';
		} else {
			$url = 1 === $kind ? 'https://' . strtolower( $base ) . '.currencyrate.today/' . $quote : 'https://currencyconvert.online/exchange-rates' . ( 'USD' === $base ? '' : '/' . strtolower( $base ) );
			$html .= '<footer class="er-caption" aria-label="' . $footer_label . '"><span class="er-attribution er-attribution-credit-first"><span class="er-credit">' . ( ! $external ? $marker : '' ) . self::link( $url, 1 === $kind ? 'CurrencyRate' : 'FX Source' ) . '</span>';
			if ( $external ) { $html .= '<span class="er-source-colon" aria-hidden="true">:</span><span class="er-source" title="' . esc_attr( $source['name'] ) . '">' . $marker . esc_html( $source['compact'] ) . '</span>'; }
			$html .= '</span><span class="er-dot er-date-dot" aria-hidden="true">·</span>' . $control . '</footer>';
		}
		return $html . '</div>';
	}

	/** One document-level native dialog; closed even when CSS or JavaScript fails. */
	public static function render_dialog() {
		if ( ! self::$needed || self::$rendered ) { return; }
		self::$rendered = true;
		$external = '<span class="er-dialog-external" aria-hidden="true">↗</span>';
		?>
		<dialog hidden style="display:none!important" id="exchange-rates-information" aria-modal="true" aria-labelledby="exchange-rates-information-title">
			<div class="er-dialog-head"><h2 id="exchange-rates-information-title" tabindex="-1" data-er-initial-focus><?php esc_html_e( 'Data details', 'exchange-rates' ); ?></h2><button class="er-dialog-close" type="button" aria-label="<?php esc_attr_e( 'Close information', 'exchange-rates' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
			<dl class="er-dialog-details">
				<div><dt><?php esc_html_e( 'Source', 'exchange-rates' ); ?></dt><dd><a data-er-modal-source target="_blank" rel="noreferrer noopener nofollow"><span data-er-modal-source-name class="er-dialog-link-label"></span><?php echo $external; ?><span class="er-a11y-sr-only"> <?php esc_html_e( '(opens in a new tab)', 'exchange-rates' ); ?></span></a></dd></div>
				<div><dt title="<?php esc_attr_e( 'When the data service saved this entry; the rates may be unchanged', 'exchange-rates' ); ?>"><?php esc_html_e( 'Check:', 'exchange-rates' ); ?></dt><dd><time data-er-modal-recorded><?php esc_html_e( 'Not available', 'exchange-rates' ); ?></time></dd></div>
				<div><dt title="<?php esc_attr_e( 'Date of the source data', 'exchange-rates' ); ?>"><?php esc_html_e( 'Latest change:', 'exchange-rates' ); ?></dt><dd><time data-er-modal-date><?php esc_html_e( 'Not available', 'exchange-rates' ); ?></time></dd></div>
			</dl>
			<section class="er-dialog-disclaimer" aria-labelledby="exchange-rates-disclaimer-title"><h3 id="exchange-rates-disclaimer-title"><?php esc_html_e( 'Disclaimer', 'exchange-rates' ); ?></h3><p><?php
				/* translators: %s: fixed plugin search marker. */
				echo esc_html( sprintf( __( 'This %s widget displays exchange rates for informational purposes only. This plugin and the website displaying it cannot guarantee their accuracy or timeliness. Rates may differ from those offered by your bank or payment provider. Confirm the applicable rate before making a transaction.', 'exchange-rates' ), '[ExchangeRatesWP]' ) );
			?></p></section>
			<footer class="er-dialog-plugin"><span class="er-dialog-plugin-identity"><span aria-hidden="true">⚡</span><?php echo self::link( 'https://wordpress.org/plugins/exchange-rates/', '<span class="er-dialog-link-label">Exchange Rates</span>' . $external, true ); ?><span class="er-dialog-version"><span aria-hidden="true">·</span> <?php echo esc_html( Plugin::VERSION ); ?> <span aria-hidden="true">🚀</span></span></span><span class="er-dialog-api"><span class="er-dialog-api-label">API:</span><?php echo self::link( 'https://currencyrate.today/', '<span class="er-dialog-link-label">CurrencyRate</span>' . $external, true ); ?></span></footer>
		</dialog>
		<?php
	}
}
