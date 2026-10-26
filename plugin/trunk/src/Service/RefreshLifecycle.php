<?php

namespace Dejurin\ExchangeRates\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Fence requests loaded before a deactivation, including reactivation cycles. */
class RefreshLifecycle {
	const OPTION = 'exchange-rates_refresh_generation';
	private static $initialized = false;
	private static $generation;

	/** Missing markers keep upgrades compatible without requiring reactivation. */
	public static function generation() {
		if ( ! self::$initialized ) {
			self::$generation = self::persisted_generation();
			self::$initialized = true;
		}
		return self::$generation;
	}

	private static function persisted_generation() {
		global $wpdb;
		// Cancellation must bypass request-local and persistent option caches.
		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::OPTION ) );
	}

	public static function matches( $generation ) {
		return $generation === self::persisted_generation();
	}

	/** An explicit activation may start new work in the same PHP request. */
	public static function activate() {
		self::$initialized = false;
		self::generation();
	}

	public static function cancel() {
		$generation = wp_generate_uuid4();
		update_option( self::OPTION, $generation, false );
		return $generation === self::persisted_generation();
	}
}
