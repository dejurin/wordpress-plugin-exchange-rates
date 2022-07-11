<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections;

use Dejurin\ExchangeRates\Plugin;

class Settings
{
    public static function init()
    {
        self::register_settings();
        self::register_sections();
        self::register_fields();
    }

    public static function register_settings()
    {
        register_setting(
            Plugin::PLUGIN_SLUG.'-general',
            \Dejurin\ExchangeRates\Models\Settings::$option_name,
            [__CLASS__, 'sanitize']
        );
    }

    public static function register_sections()
    {
        add_settings_section(
            'source_id',
            null,
            null,
            Plugin::PLUGIN_SLUG.'-general'
        );
        add_settings_section(
            'currency_format',
            null,
            null,
            Plugin::PLUGIN_SLUG.'-general'
        );
        add_settings_section(
            'decimals',
            null,
            null,
            Plugin::PLUGIN_SLUG.'-general'
        );
    }

    public static function register_fields()
    {
        Fields\Source::register();
        Fields\Format::register();
        Fields\Decimals::register();
    }

    public static function sanitize($values)
    {
        $current_options = get_option(\Dejurin\ExchangeRates\Models\Settings::$option_name, []);
        $current_options = wp_parse_args($current_options, \Dejurin\ExchangeRates\Models\Settings::get_defaults());

        if (isset($values['source_id'])) {
            \Dejurin\ExchangeRates\Models\DataSources::getInstance()->get_sources_data();
        }
        $values = wp_parse_args($values, $current_options);

        return $values;
    }

    public static function render()
    {
        echo '<p>',esc_html__('Different data providers may provide different sets of currencies and their prices. We recommend you select local data provider.', 'exchange-rates'),'</p>';
    }
}
