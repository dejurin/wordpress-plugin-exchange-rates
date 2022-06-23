<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections\Fields;

use Dejurin\ExchangeRates\Models\CurrencyFormat;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;

class Format
{
    public static function register()
    {
        add_settings_field(
            'currency_format',
            __('Formatting', Plugin::PLUGIN_SLUG),
            [__CLASS__, 'render'],
            Plugin::PLUGIN_SLUG.'-general',
            'currency_format',
            [
                'id' => 'currency_format',
                'label_for' => Plugin::PLUGIN_SLUG.'[currency_format]',
            ]
        );
    }

    public static function render($args)
    {
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults()); ?>

		<select id="<?php echo Plugin::PLUGIN_SLUG; ?> - <?php echo $args['id']; ?>" name="<?php echo Plugin::PLUGIN_SLUG; ?>[<?php echo $args['id']; ?>]">
        <?php foreach (CurrencyFormat::get_list() as $key => $value) {
            printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($key),
            selected($key, $settings[$args['id']], false),
            esc_html($value['name'])
        );
        } ?>
		</select>
		<?php
    }
}
