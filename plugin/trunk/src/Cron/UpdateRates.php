<?php

namespace Dejurin\ExchangeRates\Cron;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\RatesData;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

class UpdateRates {
	public static $action_name = Plugin::PLUGIN_SLUG . '_data_update';
	public static $retry_action_name = Plugin::PLUGIN_SLUG . '_data_retry';
	private static $lock_option = Plugin::PLUGIN_SLUG . '_refresh_lock';
	private static $retry_transient = Plugin::PLUGIN_SLUG . '_refresh_after';
	const RETRY_DELAY = 300;
	const LOCK_LIFETIME = 300;

	public static function register_task() {
		if ( wp_next_scheduled( self::$action_name ) ) {
			return true;
		}
		$token = self::acquire_lock();
		if ( false === $token ) {
			return false;
		}
		try {
			return wp_next_scheduled( self::$action_name ) ? true : wp_schedule_event( time(), 'hourly', self::$action_name );
		} finally {
			self::release_lock( $token );
		}
	}

	/** Queue work only. Rendering must never wait for the rate provider. */
	public static function queue_if_due() {
		if ( ! self::is_due() ) {
			return false;
		}
		if ( wp_next_scheduled( self::$retry_action_name ) ) {
			return true;
		}
		$hourly = wp_next_scheduled( self::$action_name );
		if ( $hourly && $hourly <= time() ) {
			return true;
		}
		$token = self::acquire_lock();
		if ( false === $token ) {
			return false;
		}
		try {
			return self::schedule_retry( max( time(), (int) get_transient( self::$retry_transient ) ) );
		} finally {
			self::release_lock( $token );
		}
	}

	private static function is_due() {
		$settings = get_option( Settings::$option_name, array() );
		$updated = isset( $settings['update_timestamp'] ) ? (int) $settings['update_timestamp'] : 0;
		return $updated <= 0 || $updated + HOUR_IN_SECONDS < time() || ! RatesData::has_current_rates();
	}

	private static function schedule_retry( $timestamp ) {
		// Recheck under the lease so concurrent page requests share one event.
		return wp_next_scheduled( self::$retry_action_name ) ? true : wp_schedule_single_event( $timestamp, self::$retry_action_name );
	}

	/** Preserve the existing hourly hook, including an explicit source argument. */
	public static function run_hourly( $force_source_id = null ) {
		return self::refresh( $force_source_id );
	}

	public static function run_retry() {
		// An explicit/manual update may already have refreshed this cache.
		if ( ! self::is_due() ) {
			delete_transient( self::$retry_transient );
			wp_clear_scheduled_hook( self::$retry_action_name );
			return true;
		}
		return self::refresh();
	}

	private static function refresh( $force_source_id = null ) {
		$token = self::acquire_lock();
		if ( false === $token ) {
			return false;
		}
		try {
			$retry_at = (int) get_transient( self::$retry_transient );
			if ( $retry_at > time() ) {
				self::schedule_retry( $retry_at );
				return false;
			}
			if ( UpdateDataSources::update( $force_source_id ) ) {
				delete_transient( self::$retry_transient );
				wp_clear_scheduled_hook( self::$retry_action_name );
				return true;
			}
			// Attempt time is separate from the last successful update metadata.
			$retry_at = time() + self::RETRY_DELAY;
			set_transient( self::$retry_transient, $retry_at, self::RETRY_DELAY );
			self::schedule_retry( $retry_at );
			return false;
		} finally {
			self::release_lock( $token );
		}
	}

	/**
	 * Acquire a per-site lease without depending on persistent object caching.
	 *
	 * add_option() can overwrite a competing insert through its upsert. Use a
	 * unique insert and compare-and-swap for an expired lease instead.
	 *
	 * @return string|false Ownership token, or false when busy/storage failed.
	 */
	private static function acquire_lock() {
		global $wpdb;
		$token = ( time() + self::LOCK_LIFETIME ) . ':' . wp_generate_uuid4();
		$inserted = $wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
			self::$lock_option, $token, 'no'
		) );
		if ( 1 === $inserted ) {
			self::clear_lock_cache();
			return $token;
		}
		if ( false === $inserted ) {
			return false;
		}
		$previous = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::$lock_option ) );
		if ( null === $previous || (int) $previous > time() ) {
			return false;
		}
		$replaced = $wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
			$token, self::$lock_option, $previous
		) );
		if ( 1 !== $replaced ) {
			return false;
		}
		self::clear_lock_cache();
		return $token;
	}

	private static function release_lock( $token ) {
		global $wpdb;
		// An expired worker must never release a successor's lease.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::$lock_option, $token ) );
		self::clear_lock_cache();
	}

	private static function clear_lock_cache() {
		wp_cache_delete( self::$lock_option, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	public static function clear_tasks() {
		wp_clear_scheduled_hook( self::$action_name );
		wp_clear_scheduled_hook( self::$retry_action_name );
		delete_transient( self::$retry_transient );
		delete_option( self::$lock_option );
	}
}
