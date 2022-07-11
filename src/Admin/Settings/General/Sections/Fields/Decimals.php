<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections\Fields;

use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;

class Decimals
{
    public static function register()
    {
        add_settings_field(
            'decimals',
            esc_html__('Decimals', 'exchange-rates'),
            [__CLASS__, 'render'],
            Plugin::PLUGIN_SLUG.'-general',
            'decimals',
            [
                'id' => 'decimals',
                'label_for' => Plugin::PLUGIN_SLUG.'[decimals]',
            ]
        );
    }

    public static function render($args)
    {
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults()); ?>

        <p><input 
        id="<?php echo Plugin::PLUGIN_SLUG,'-',esc_attr($args['id']); ?>"
        name="<?php echo Plugin::PLUGIN_SLUG; ?>[<?php echo esc_attr($args['id']); ?>]"
        type="range"
        step="1"
        min="0"
        max="7"
        value="<?php echo esc_attr($settings[$args['id']]); ?>">
        <span id="<?php echo Plugin::PLUGIN_SLUG,'-',esc_html($args['id']); ?>-show"><?php echo esc_html($settings[$args['id']]); ?></span></p>
		<?php
    }
}
