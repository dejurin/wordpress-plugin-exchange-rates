<?php

namespace Dejurin\ExchangeRates\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Models\Checkbox;
use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\CurrencyConverter;
use Dejurin\ExchangeRates\Service\RatesData;

class Converter extends \WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            Plugin::PLUGIN_SLUG.'_currency-converter',
            "🏦 ".esc_html__('Currency Converter Widget', 'exchange-rates'),
            [
                'classname' => 'cr-'.Plugin::PLUGIN_SLUG,
                'description' => esc_html__('Currency Converter Widget', 'exchange-rates'),
            ]
        );

        Plugin::register_public_script_style();
    }

    public function widget($args, $instance)
    {
        $instance = $this->_merge_instance_with_default_instance($instance);

        $object = new CurrencyConverter();
        $object->parameters = [
            'title' => (string) sanitize_text_field($instance['title']),
            'amount' => (float) sanitize_text_field($instance['amount']),
            'from' => (string) sanitize_text_field($instance['from']),
            'to' => (string) sanitize_text_field($instance['to']),
            'code' => (bool) sanitize_text_field($instance['code']),
            'border' => (bool) sanitize_text_field($instance['border']),
            'symbol' => (bool) sanitize_text_field($instance['symbol']),
            'after' => (bool) sanitize_text_field($instance['after']),
        ];

        echo wp_kses_post($args['before_widget']);
        echo wp_kses($object->get_html_widget($this->number), array_merge(wp_kses_allowed_html('post'), array('input' => array('value' => true,'type' => true,'name' => true))));
        echo wp_kses_post($args['after_widget']);
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
        $get_sources = Sources::get_list();
        $instance = $this->_merge_instance_with_default_instance($instance);
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());
        $rates = RatesData::get();
        $currency_list = RatesData::get_rate_codes($rates);

        if (empty($currency_list)) { ?>
            <p><?php esc_html_e('Exchange-rate data is not available yet. Save plugin settings or force a data update first.', 'exchange-rates'); ?></p>
            <?php
            return;
        }

        if ('currencyrate' !== $settings['source_id']) {
            $first_element = end($currency_list);
            array_pop($currency_list);
            array_unshift($currency_list, $first_element);
        } ?>
        <div id="plugin-<?php echo esc_attr(Plugin::PLUGIN_SLUG); ?>-admin">
        <p><b><?php esc_html_e('Exchange rate source:', 'exchange-rates'); ?></b> <?php echo esc_html(isset($get_sources[$settings['source_id']]) ? $get_sources[$settings['source_id']]['name'] : __('Unknown source', 'exchange-rates')); ?></p>
        <fieldset style="padding:5px 15px;margin-bottom:15px">
            <legend><?php esc_html_e('Titles', 'exchange-rates'); ?></legend>
            <p>
                <label for="<?php echo esc_html($this->get_field_id('title')); ?>">
                    <?php esc_html_e('Title:', 'exchange-rates'); ?>
                    <input 
                        id="<?php echo esc_html($this->get_field_id('title')); ?>"
                        name="<?php echo esc_html($this->get_field_name('title')); ?>"
                        value="<?php echo esc_attr($instance['title']); ?>"
                        type="text">
                </label>
            </p>
            <p>
                <small><?php esc_html_e('Title of widget.', 'exchange-rates'); ?></small>
            </p>
        </fieldset>
        <fieldset style="padding:5px 15px;margin-bottom:15px">
        <legend><?php esc_html_e('Currency', 'exchange-rates'); ?></legend>
        <p>
			<label for="<?php echo esc_html($this->get_field_id('amount')); ?>">
                <?php esc_html_e('Amount:', 'exchange-rates'); ?>
            <input 
                id="<?php echo esc_html($this->get_field_id('amount')); ?>"
                name="<?php echo esc_html($this->get_field_name('amount')); ?>"
                value="<?php echo esc_html($instance['amount']); ?>"
                type="text">
            </label>
        </p>
		<p>
            <small><?php esc_html_e('Amount multiplied by the rate.', 'exchange-rates'); ?></small>
        </p>
		<p>
            <label for="<?php echo esc_html($this->get_field_id('from')); ?>"><?php esc_html_e('Base currency:', 'exchange-rates'); ?></label>
            <select id="<?php echo esc_html($this->get_field_id('from')); ?>" name="<?php echo esc_html($this->get_field_name('from')); ?>">
			<?php

        foreach ($currency_list as $value) {
            if (!isset($get_currencies[$value])) {
                continue;
            }

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
            <small><?php esc_html_e('The currency in which will be settled other currencies.', 'exchange-rates'); ?></small>
        </p>
        <p>
            <label for="<?php echo esc_html($this->get_field_id('to')); ?>"><?php esc_html_e('Quote currency:', 'exchange-rates'); ?></label>
            <select id="<?php echo esc_html($this->get_field_id('to')); ?>" name="<?php echo esc_html($this->get_field_name('to')); ?>">
            <?php foreach ($currency_list as $value) {
            if (!isset($get_currencies[$value])) {
                continue;
            }

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
            <small><?php esc_html_e('The currency in which will be settled other currencies.', 'exchange-rates'); ?></small>
        </p>
        </fieldset>
        <fieldset style="padding:5px 15px;margin-bottom:15px">
            <legend><?php esc_html_e('Options', 'exchange-rates'); ?></legend>
            <?php $get_list = array_slice(Checkbox::get_list(), 4, 4);
        foreach ($get_list as $key => $value) {
            echo sprintf(
                    '<p><label><input type="checkbox" name="%3$s" value="%1$s"%2$s>%4$s</label></p>',
                    esc_attr($key),
                    checked(true, $instance[$key], false),
                    esc_html($this->get_field_name($key)),
                    esc_html($value)
                );
        } ?>
        </fieldset>
        <hr>
        <h3><?php esc_html_e('Shortcode', 'exchange-rates'); ?></h3>
        <p><small><?php esc_html_e('If you only need a widget, ignore this shortcode.', 'exchange-rates'); ?><br/>
        <?php esc_html_e('If you want to put the widget anywhere on your website/blog, use the shortcode.', 'exchange-rates'); ?></small></p>
        <input type="hidden" name="id" value="<?php echo esc_html(time()); ?>">
		<textarea name="shortcode-generator" style="width:100%" rows="8" onclick="this.focus();this.select()" readonly></textarea>
        <div style="float:right"><button class="button button-primary" disabled><?php esc_html_e('Generate', 'exchange-rates'); ?></button></div>
        </div>
        <?php
    }

    private function _merge_instance_with_default_instance($instance)
    {
        $settings = [
            'title' => esc_html__('Currency Converter', 'exchange-rates'),
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
}
