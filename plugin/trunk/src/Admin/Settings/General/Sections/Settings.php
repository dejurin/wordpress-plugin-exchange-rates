<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections;

if (!defined('ABSPATH')) {
    exit;
}

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

        if (!is_array($values)) {
            $values = [];
        }

        if (isset($values['source_id'])) {
            \Dejurin\ExchangeRates\Models\DataSources::getInstance()->get_sources_data();
        }

        $sanitized = $current_options;
        $sources = \Dejurin\ExchangeRates\Models\Sources::get_list();
        $formats = \Dejurin\ExchangeRates\Models\CurrencyFormat::get_list();

        if (isset($values['source_id'])) {
            $source_id = sanitize_key($values['source_id']);
            if (isset($sources[$source_id])) {
                $sanitized['source_id'] = $source_id;
            }
        }

        if (isset($values['currency_format'])) {
            $currency_format = (int) $values['currency_format'];
            if (isset($formats[$currency_format])) {
                $sanitized['currency_format'] = $currency_format;
            }
        }

        if (isset($values['decimals'])) {
            $sanitized['decimals'] = max(0, min(7, (int) $values['decimals']));
        }

        return $sanitized;
    }

    public static function render()
    {
        echo '<p>',esc_html__('Different data providers may provide different sets of currencies and their prices. We recommend you select local data provider.', 'exchange-rates'),'</p>';
    }
}
