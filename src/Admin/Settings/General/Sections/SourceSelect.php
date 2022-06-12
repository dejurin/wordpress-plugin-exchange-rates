<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections;

use Dejurin\ExchangeRates\Plugin;

class SourceSelect
{
    public static function init()
    {
        self::register_settings();
        self::register_sections();
        self::register_fields();
    }

    public static function register_settings() {
		register_setting(
			Plugin::PLUGIN_SLUG . '-general',
			\Dejurin\ExchangeRates\Models\Settings::$option_name,
			array( __CLASS__, 'sanitize' )
		);
	}

    public static function register_sections()
    {
        add_settings_section(
            'data_source',
            __('Settings', Plugin::PLUGIN_SLUG),
            null,
            Plugin::PLUGIN_SLUG.'-general'
        );
    }

    public static function register_fields()
    {
        Fields\Source::register();
    }


    public static function sanitize( $values ) {
		/**
		 * Идея фикс.
		 * 1) Получаем опции из БД (какие есть).
		 * 2) Мерджим их с дефолтными (тем самым дополняя к бдшным те, что появились в новой версии плагина и т п).
		 * 3) Мерджим отфильтрованные опции с теми, что получили в пункте 2).
		 *
		 * Проблема: если мы не выполняем сохранение настроек, то при обновлении структуры опций есть шанс опять иметь в БД не все дефолтные настройки.
		 */

		// Получаем настройки из бд и добавляем к ним дефолтные
		$current_options = get_option( \Dejurin\ExchangeRates\Models\Settings::$option_name, array() );
		$current_options = wp_parse_args( $current_options, \Dejurin\ExchangeRates\Models\Settings::get_defaults() );

		if( isset( $values['data_provider'] ) ) {
			$providers = \Dejurin\ExchangeRates\Models\DataSources::getInstance()->get_providers();

			if( !array_key_exists( $values['data_provider'], $providers ) ) {
				$values['data_provider'] = $current_options['data_provider'];
				//$filtered_values['data_provider_name'] = sanitize_text_field( $values['data_provider_name'] );
			}
		}

		// Соединяем дефолотные + текущие с теми, что были введены сейчас
		$values = wp_parse_args( $values, $current_options );

		return $values;
	}

	public static function render() {
		echo '<p>';
		_e( 'Different data providers may provide different sets of currencies and their prices. We recommend you select local data provider.',  Plugin::NAME );
		echo '</p>';
	}
}
