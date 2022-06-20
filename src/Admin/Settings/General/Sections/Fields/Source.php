<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections\Fields;

use Dejurin\ExchangeRates\Models\DataSources;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;

class Source
{
    public static function register()
    {
        add_settings_field(
            'source_id',
            '<a href="#source-table">'.__('Sources', Plugin::PLUGIN_SLUG).'</a>',
            [__CLASS__, 'render'],
            Plugin::PLUGIN_SLUG.'-general',
            'source_id',
            [
                'id' => 'source_id',
                'label_for' => Plugin::PLUGIN_SLUG.'[source_id]',
            ]
        );
    }

    public static function render($args)
    {
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());

        $get_sources = Sources::get_list();

        $data_cources = DataSources::getInstance();
        $sources = $data_cources->get_sources_data(); ?>

		<select id="<?php echo Plugin::PLUGIN_SLUG; ?>[<?php echo $args['id']; ?>]" name="<?php echo Plugin::PLUGIN_SLUG; ?>[<?php echo $args['id']; ?>]">
        <?php foreach ($sources['data'] as $value) {
            printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($value['source']),
            selected($value['source'], $settings[$args['id']], false),
            (isset($get_sources[$value['source']])) ? $get_sources[$value['source']]['name'] : esc_html($value['source'])
        );
        } ?>
		</select>
		<?php
    }
}
