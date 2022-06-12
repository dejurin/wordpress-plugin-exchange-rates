<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections\Fields;

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;

class Source
{
    public static function register()
    {
        add_settings_field(
            'data_source',
            '<a href="#source-table">'.__('Sources', Plugin::PLUGIN_SLUG).'</a>',
            [__CLASS__, 'render'],
            Plugin::PLUGIN_SLUG.'-general',
            'data_source',
            ['label_for' => Plugin::PLUGIN_SLUG.'__[source_id]']
        );
    }

    public static function render()
    {
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());

        $get_sources = Sources::get_sources();
 
        $sources = get_transient(Plugin::PLUGIN_SLUG.'_sources'); ?>
		<select id="<?php echo Plugin::PLUGIN_SLUG; ?>__[source_id]" name="<?php echo Plugin::PLUGIN_SLUG; ?>[source_id]">

        <?php foreach ($sources['data'] as $value) {
            printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($value['source']),
            selected($value['source'], $settings['source_id'], false),
            (isset($get_sources[$value['source']])) ? $get_sources[$value['source']]['name'] : esc_html($value['source'])
        );
        } ?>
		</select>
		<?php
    }
}
