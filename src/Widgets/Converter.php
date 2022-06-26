<?php

namespace Dejurin\ExchangeRates\Widgets;

use Dejurin\ExchangeRates\Models\Checkbox;
use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\CurrencyConverter;

class Converter extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            Plugin::PLUGIN_SLUG . '_currency-converter',
            "All Banks \u{1F3E6} ".__('Currency Converter Widget', Plugin::PLUGIN_SLUG),
            [
                'classname' => 'exchange-rates',
                'description' => __('Currency Converter Widget', Plugin::PLUGIN_SLUG),
            ]
        );

        add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_script_style']);
    }

    public function widget($args, $instance)
    {
        $instance = $this->_merge_instance_with_default_instance($instance);

        $object = new CurrencyConverter();
        $object->parameters = [
            'title' => (string) $instance['title'],
            'amount' => (float) $instance['amount'],
            'base_currency' => (string) $instance['base_currency'],
            'quote_currency' => (string) $instance['quote_currency'],
            'code' => (bool) $instance['code'],
            'border' => (bool) $instance['border'],
            'symbol' => (bool) $instance['symbol'],
            'after' => (bool) $instance['after'],
        ];

        echo $args['before_widget'];
        echo $object->get_html_widget($this->number);
        echo $args['after_widget'];
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $this->_merge_instance_with_default_instance($new_instance);

        $instance_to_save['title'] = (string) sanitize_text_field($instance['title']);
        $instance_to_save['amount'] = (float) sanitize_text_field($instance['amount']);
        $instance_to_save['base_currency'] = (string) strtoupper(sanitize_text_field($instance['base_currency']));
        $instance_to_save['quote_currency'] = (string) strtoupper(sanitize_text_field($instance['quote_currency']));
        $instance_to_save['code'] = (bool) sanitize_text_field($instance['code']);
        $instance_to_save['after'] = (bool) sanitize_text_field($instance['after']);
        $instance_to_save['symbol'] = (bool) sanitize_text_field($instance['symbol']) && isset($new_instance['symbol']);
        $instance_to_save['border'] = (bool) sanitize_text_field($instance['border']) && isset($new_instance['border']);

        return $instance_to_save;
    }

    public function form($instance)
    {
        $get_currencies = Currencies::get_list();
        $instance = $this->_merge_instance_with_default_instance($instance);
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        $currency_list = array_keys($rates['data'][0]['rates']); ?>

        <fieldset style="padding:5px 15px;margin-bottom:15px">
            <legend><?php _e('Titles', Plugin::PLUGIN_SLUG); ?></legend>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>">
                    <?php _e('Title:', Plugin::PLUGIN_SLUG); ?>
                </label>
                <input 
                    id="<?php echo $this->get_field_id('title'); ?>"
                    name="<?php echo $this->get_field_name('title'); ?>"
                    value="<?php echo esc_attr($instance['title']); ?>"
                    type="text">
            </p>
            <p>
                <small><?php _e('title', Plugin::PLUGIN_SLUG); ?></small>
            </p>
        </fieldset>
        <fieldset style="padding:5px 15px;margin-bottom:15px">
        <legend><?php _e('Currency', Plugin::PLUGIN_SLUG); ?></legend>
        <p>
			<label for="<?php echo $this->get_field_id('amount'); ?>">
                <?php _e('Amount:', Plugin::PLUGIN_SLUG); ?>
            </label>
            <input 
                id="<?php echo $this->get_field_id('amount'); ?>"
                name="<?php echo $this->get_field_name('amount'); ?>"
                value="<?php echo esc_attr($instance['amount']); ?>"
                type="text">
        </p>
		<p>
            <small><?php _e('Amount multiplied by the rate.', Plugin::PLUGIN_SLUG); ?></small>
        </p>
		<p>
            <label for="<?php echo $this->get_field_id('base_currency'); ?>"><?php _e('Base currency:', Plugin::PLUGIN_SLUG); ?></label>
            <select id="<?php echo $this->get_field_id('base_currency'); ?>" name="<?php echo $this->get_field_name('base_currency'); ?>">
			<?php

        foreach ($currency_list as $value) {
            printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($value),
                        selected($value, $instance['base_currency'], false),
                        esc_html($value.' - '.$get_currencies[$value]['name'])
                    );
        } ?>
            </select>
        </p>
		<p>
            <small><?php _e('The currency in which will be settled other currencies.', Plugin::PLUGIN_SLUG); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('quote_currency'); ?>"><?php _e('Quote currency:', Plugin::PLUGIN_SLUG); ?></label>
            <select id="<?php echo $this->get_field_id('quote_currency'); ?>" name="<?php echo $this->get_field_name('quote_currency'); ?>">
            <?php foreach ($currency_list as $value) {
            printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($value),
                        selected($value, $instance['quote_currency'], false),
                        esc_html($value.' - '.$get_currencies[$value]['name'])
                    );
        } ?>
            </select>
        </p>
		<p>
            <small><?php _e('The currency in which will be settled other currencies.', Plugin::PLUGIN_SLUG); ?></small>
        </p>
        </fieldset>
        <fieldset style="padding:5px 15px;margin-bottom:15px">
            <legend><?php _e('Options', Plugin::PLUGIN_SLUG); ?></legend>
            <?php $get_list = array_slice(Checkbox::get_list(), 6, 4);
        foreach ($get_list as $key => $value) {
            echo sprintf(
                    '<p><input type="checkbox" id="%1$s" name="%3$s" value="%1$s"%2$s><label for="%1$s">%4$s</label></p>',
                    esc_attr($key),
                    checked(true, $instance[$key], false),
                    $this->get_field_name($key),
                    $value
                );
        } ?>
        </fieldset>
		<?php
    }

    private function _merge_instance_with_default_instance($instance)
    {
        $settings = [
            'title' => __('Currency Converter', Plugin::PLUGIN_SLUG),
            'amount' => 1,
            'base_currency' => 'USD',
            'quote_currency' => 'EUR',
            'code' => false,
            'after' => false,
            'border' => true,
            'symbol' => true,
        ];

        return wp_parse_args($instance, $settings);
    }

    public function wp_enqueue_script_style()
    {
        // Enqueue styles if theme don't support our plugin and widget is active.
        if (!current_theme_supports('plugin-'.Plugin::PLUGIN_SLUG) &&
            is_active_widget(false, false, $this->id_base)) {
            wp_enqueue_style('plugin-'.Plugin::PLUGIN_SLUG.'-widgets');
        }
    }
}
