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
            'source_id',
            '<a href="#source-table">'.__('Sources', 'exchange-rates').'</a>',
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
        $get_sources = Sources::get_list(); ?>

		<select id="<?php echo Plugin::PLUGIN_SLUG,'-',esc_attr($args['id']); ?>" name="<?php echo Plugin::PLUGIN_SLUG; ?>[<?php echo esc_attr($args['id']); ?>]">
        <?php foreach ($get_sources as $key => $value) {
            printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($key),
            selected($key, $settings[$args['id']], false),
            esc_html($value['name']));
        } ?>
		</select>
		<?php
    }
}
