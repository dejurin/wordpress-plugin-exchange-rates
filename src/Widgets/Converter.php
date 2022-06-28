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
            Plugin::PLUGIN_SLUG.'_currency-converter',
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
            'from' => (string) $instance['from'],
            'to' => (string) $instance['to'],
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
        $instance_to_save['from'] = (string) strtoupper(sanitize_text_field($instance['from']));
        $instance_to_save['to'] = (string) strtoupper(sanitize_text_field($instance['to']));
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
        $currency_list = array_keys($rates['data'][0]['rates']);
        $first_element = end($currency_list);
        array_pop($currency_list);
        array_unshift($currency_list, $first_element); ?>

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
                <small><?php _e('Title of widget.', Plugin::PLUGIN_SLUG); ?></small>
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
            <label for="<?php echo $this->get_field_id('from'); ?>"><?php _e('Base currency:', Plugin::PLUGIN_SLUG); ?></label>
            <select id="<?php echo $this->get_field_id('from'); ?>" name="<?php echo $this->get_field_name('from'); ?>">
			<?php

        foreach ($currency_list as $value) {
            printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($value),
                        selected($value, $instance['from'], false),
                        esc_html($value.' - '.$get_currencies[$value]['name'])
                    );
        } ?>
            </select>
        </p>
		<p>
            <small><?php _e('The currency in which will be settled other currencies.', Plugin::PLUGIN_SLUG); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('to'); ?>"><?php _e('Quote currency:', Plugin::PLUGIN_SLUG); ?></label>
            <select id="<?php echo $this->get_field_id('to'); ?>" name="<?php echo $this->get_field_name('to'); ?>">
            <?php foreach ($currency_list as $value) {
            printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($value),
                        selected($value, $instance['to'], false),
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
            <?php $get_list = array_slice(Checkbox::get_list(), 4, 4);
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
            'from' => 'USD',
            'to' => 'EUR',
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
