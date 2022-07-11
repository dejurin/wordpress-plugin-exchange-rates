<?php

namespace Dejurin\ExchangeRates\Widgets;

use Dejurin\ExchangeRates\Models\Checkbox;
use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Flags;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Models\TableColumns;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\CurrencyTable;

class Table extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            Plugin::PLUGIN_SLUG.'_currency-table',
            "🏦 ".esc_html__('Currency Table Widget', 'exchange-rates'),
            [
                'classname' => 'cr-'.Plugin::PLUGIN_SLUG,
                'description' => esc_html__('A table with currency rates.', 'exchange-rates'),
            ]
        );

        add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_script_style']);
    }

    public function widget($args, $instance)
    {
        $instance = $this->_merge_instance_with_default_instance($instance);
        echo $args['before_widget'];
        /*
         * Table
         */
        if (!empty($instance['currency_list'])) {
            // var_dump($this->parameters['currency_list']);

            // $instance['currency_list'] = str_replace(' ', '', $instance['currency_list']);
            // $instance['currency_list'] = explode(',', trim($instance['currency_list'], ',')); // add trim comma

            if (!empty($instance['base_currency'])) {
                $object = new CurrencyTable();
                $object->parameters = [
                        'amount' => (float) sanitize_text_field($instance['amount']),
                        'base_currency' => (string) sanitize_text_field($instance['base_currency']),
                        'currency_list' => (array) $instance['currency_list'],
                        'decimals' => (int) sanitize_text_field($instance['decimals']),
                        'flag_size' => (int) sanitize_text_field($instance['flag_size']),
                        'flag_type' => (string) sanitize_text_field($instance['flag_type']),
                        'currency_format' => (int) sanitize_text_field($instance['currency_format']),
                        'inverse' => (bool) sanitize_text_field($instance['inverse']),
                        'code' => (bool) sanitize_text_field($instance['code']),
                        'region' => (bool) sanitize_text_field($instance['region']),
                        'full_width' => (bool) sanitize_text_field($instance['full_width']),
                        'amount_active' => (bool) sanitize_text_field($instance['amount_active']),
                        'base_show' => (bool) sanitize_text_field($instance['base_show']),
                        'border' => (bool) sanitize_text_field($instance['border']),
                        'border_hori' => (bool) sanitize_text_field($instance['border_hori']),
                        'border_vert' => (bool) sanitize_text_field($instance['border_vert']),
                        'after' => (bool) sanitize_text_field($instance['after']),
                        'symbol' => (bool) sanitize_text_field($instance['symbol']),
                        'table_headers_show' => (bool) sanitize_text_field($instance['table_headers_show']),
                        'table_headers_name' => (string) sanitize_text_field($instance['table_headers_name']),
                        'table_headers_code' => (string) sanitize_text_field($instance['table_headers_code']),
                        'table_headers_mid' => (string) sanitize_text_field($instance['table_headers_mid']),
                        'table_headers_previous_close' => (string) sanitize_text_field($instance['table_headers_previous_close']),
                        'table_headers_changes' => (string) sanitize_text_field($instance['table_headers_changes']),
                        'table_headers_code_show' => (bool) sanitize_text_field($instance['table_headers_code_show']),
                        'table_headers_previous_close_show' => (bool) sanitize_text_field($instance['table_headers_previous_close_show']),
                        'table_headers_changes_show' => (bool) sanitize_text_field($instance['table_headers_changes_show']),
                    ];
                echo $object->get_html_widget($this->number);
            }
        }

        echo $args['after_widget'];
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $this->_merge_instance_with_default_instance($new_instance);
        $instance_to_save['amount'] = (float) sanitize_text_field($instance['amount']);
        $instance_to_save['base_currency'] = (string) strtoupper(sanitize_text_field($instance['base_currency']));
        $instance_to_save['currency_list'] = (array) $instance['currency_list'];
        $instance_to_save['decimals'] = (int) sanitize_text_field($instance['decimals']);
        $instance_to_save['flag_size'] = (int) sanitize_text_field($instance['flag_size']);
        $instance_to_save['flag_type'] = (string) sanitize_text_field($instance['flag_type']);
        $instance_to_save['currency_format'] = (int) sanitize_text_field($instance['currency_format']);
        $instance_to_save['inverse'] = (bool) sanitize_text_field($instance['inverse']);
        $instance_to_save['code'] = (bool) sanitize_text_field($instance['code']);
        $instance_to_save['region'] = (bool) sanitize_text_field($instance['region']);
        $instance_to_save['full_width'] = (bool) sanitize_text_field($instance['full_width']);
        $instance_to_save['amount_active'] = (bool) sanitize_text_field($instance['amount_active']);
        $instance_to_save['after'] = (bool) sanitize_text_field($instance['after']);
        $instance_to_save['symbol'] = (bool) sanitize_text_field($instance['symbol']);
        $instance_to_save['border_hori'] = (bool) sanitize_text_field($instance['border_hori']);
        $instance_to_save['border_vert'] = (bool) sanitize_text_field($instance['border_vert']);
        $instance_to_save['base_show'] = (bool) sanitize_text_field($instance['base_show']) && isset($new_instance['base_show']);
        $instance_to_save['border'] = (bool) sanitize_text_field($instance['border']) && isset($new_instance['border']);

        // Table headers
        $instance_to_save['table_headers_show'] = (bool) sanitize_text_field($instance['table_headers_show']) && isset($new_instance['table_headers_show']);
        $instance_to_save['table_headers_name'] = (string) sanitize_text_field($instance['table_headers_name']);
        $instance_to_save['table_headers_code'] = (string) sanitize_text_field($instance['table_headers_code']);
        $instance_to_save['table_headers_mid'] = (string) sanitize_text_field($instance['table_headers_mid']);
        $instance_to_save['table_headers_previous_close'] = (string) sanitize_text_field($instance['table_headers_previous_close']);
        $instance_to_save['table_headers_changes'] = (string) sanitize_text_field($instance['table_headers_changes']);
        $instance_to_save['table_headers_previous_close_show'] = (bool) sanitize_text_field($instance['table_headers_previous_close_show']);
        $instance_to_save['table_headers_code_show'] = (bool) sanitize_text_field($instance['table_headers_code_show']);
        $instance_to_save['table_headers_changes_show'] = (bool) sanitize_text_field($instance['table_headers_changes_show']);

        return $instance_to_save;
    }

    public function form($instance)
    {
        $get_currencies = Currencies::get_list();
        $get_sources = Sources::get_list();
        $instance = $this->_merge_instance_with_default_instance($instance);
        $settings = get_option(Settings::$option_name, []);
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        $currency_list = array_keys($rates['data'][0]['rates']);

        if ('currencyrate' !== $settings['source_id']) {
            $first_element = end($currency_list);
            array_pop($currency_list);
            array_unshift($currency_list, $first_element);
        } ?>
    
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
		<p><small><?php esc_html_e('Amount multiplied by the rate.', 'exchange-rates'); ?></small></p>
		<p><label for="<?php echo esc_html($this->get_field_id('base_currency')); ?>"><?php esc_html_e('Base currency:', 'exchange-rates'); ?></label>
		<select id="<?php echo esc_html($this->get_field_id('base_currency')); ?>" name="<?php echo esc_html($this->get_field_name('base_currency')); ?>">
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
		<p><small><?php esc_html_e('The currency in which will be settled other currencies.', 'exchange-rates'); ?></small></p>
		<p>
            <label for="<?php echo esc_html($this->get_field_id('currency_list')); ?>"><?php esc_html_e('Currencies list:', 'exchange-rates'); ?></label>
            <select 
            multiple="multiple"
            class="resize-both"
            size="10"
            id="<?php echo esc_html($this->get_field_id('currency_list')); ?>"
            name="<?php echo esc_html($this->get_field_name('currency_list')); ?>[]">
        <?php foreach ($currency_list as $value) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                selected($value, in_array($value, $instance['currency_list']) ? $value : null, false),
                esc_html($value.' - '.$get_currencies[$value]['name'])
            );
        } ?>
            </select>
        </p>
		<p><small><?php esc_html_e('The currencies which will be displayed in table. Separate by commas.', 'exchange-rates'); ?></small></p>
        </fieldset>
        <fieldset style="padding:5px 15px;margin-bottom:15px">
        <legend><?php esc_html_e('Flag', 'exchange-rates'); ?></legend>
        <p>
            <label for="<?php echo esc_html($this->get_field_id('flag_size')); ?>"><?php esc_html_e('Flag size:', 'exchange-rates'); ?>
            <input 
                id="<?php echo esc_html($this->get_field_id('flag_size')); ?>"
                name="<?php echo esc_html($this->get_field_name('flag_size')); ?>"
                type="range"
                step="4"
                min="16"
                max="64"
                value="<?php echo esc_html($instance['flag_size']); ?>">
            </label>
            <span id="<?php echo esc_html($this->get_field_id('flag_size')); ?>-show"><?php echo esc_html($instance['flag_size']); ?></span>px
        </p>
        <p>
            <label for="flag-type"><?php esc_html_e('Flag type:', 'exchange-rates'); ?></label>&nbsp;
            <?php foreach (Flags::get_types() as $item) {
            echo sprintf(
                    '<label for="flag-type-%1$s"><input type="radio" id="flag-type-%1$s" name="%3$s" value="%1$s"%2$s>%4$s</label>&nbsp;&nbsp;',
                    esc_attr($item['value']),
                    checked($item['value'], $instance['flag_type'], false),
                    esc_html($this->get_field_name('flag_type')),
                    esc_html($item['name'])
                );
        } ?>
        </p>
        </fieldset>
        <fieldset style="padding:5px 15px;margin-bottom:15px">
                <legend><?php esc_html_e('Options', 'exchange-rates'); ?></legend>
                <?php $checkbox_list = Checkbox::get_list();
        unset($checkbox_list['border']);
        foreach ($checkbox_list as $key => $value) {
            echo sprintf(
                        '<p><label><input type="checkbox" name="%3$s" value="%1$s"%2$s>&nbsp;%4$s</label></p>',
                        esc_attr($key),
                        checked(true, $instance[$key], false),
                        esc_html($this->get_field_name($key)),
                        esc_html($value)
                    );
        } ?>
        </fieldset>
        <fieldset style="padding:5px 15px">
        <legend><?php esc_html_e('Table headers', 'exchange-rates'); ?></legend>
        <table><tbody>
            <tr><td scope="col" colspan="2">
            <?php echo sprintf('<label for="%1$s"><input type="checkbox" id="%1$s" name="%3$s" value="%1$s"%2$s>&nbsp;%4$s</label>',
                esc_attr('table_headers_show'),
                checked(true, $instance['table_headers_show'], false),
                esc_html($this->get_field_name('table_headers_show')),
                esc_html__('Show table header', 'exchange-rates')); ?>
            </td></tr>
        <?php $i = 0;
        foreach (TableColumns::get_list() as $key => $value) {
            ++$i; ?>
            <tr>
                <td>
                    <?php echo sprintf(
                            '<label for="%1$s"><input type="checkbox" id="%1$s" name="%3$s" value="%1$s"%2$s %5$s>&nbsp;%4$s</label>',
                            esc_attr('table_headers_'.$key.'_show'),
                            checked(true, $instance['table_headers_'.$key.'_show'], false),
                            esc_html($this->get_field_name('table_headers_'.$key.'_show')),
                            esc_html($value),
                            (!isset($instance['table_headers_'.$key.'_show'])) ? esc_html('disabled checked readonly') : ''
                    ); ?>
                </td>
                <td>
                    <input 
                        id="<?php echo esc_html($this->get_field_id('table_headers_'.$key)); ?>"
                        name="<?php echo esc_html($this->get_field_name('table_headers_'.$key)); ?>"
                        value="<?php echo esc_html($instance['table_headers_'.$key]); ?>"
                        type="text">
                </td>
            </tr>
        <?php
        } ?>
        </tbody>
        </table>
        </fieldset>
        <hr>
        <h3><?php esc_html_e('Shortcode', 'exchange-rates'); ?></h3>
        <p><small><?php esc_html_e('If you only need a widget, ignore this shortcode.', 'exchange-rates'); ?><br/>
        <?php esc_html_e('If you want to put the widget anywhere on your website/blog, use the shortcode.', 'exchange-rates'); ?></small></p>
        <input type="hidden" name="id" value="<?php echo esc_html(time()); ?>">
		<textarea name="shortcode-generator" style="width:100%" rows="8" onclick="this.focus();this.select()" readonly></textarea>
        <div style="float:right"><button class="button button-primary" disabled><?php esc_html_e('Generate', 'exchange-rates'); ?></button></div>
		<?php
    }

    private function _merge_instance_with_default_instance($instance)
    {
        $settings = [
            'amount' => 1,
            'base_currency' => 'USD',
            'currency_list' => ['EUR', 'GBP', 'AUD', 'JPY', 'BRL'],
            'flag_size' => 16,
            'flag_type' => 'rectangular',
            'decimals' => 4,
            'currency_format' => 3,
            'inverse' => false,
            'code' => false,
            'region' => false,
            'full_width' => false,
            'amount_active' => false,
            'symbol' => false,
            'after' => false,
            'base_show' => true,
            'border' => true,
            'border_hori' => false,
            'border_vert' => false,
            'table_headers_show' => true,
            'table_headers_name' => esc_html__('Currency', 'exchange-rates'),
            'table_headers_code' => esc_html__('Code', 'exchange-rates'),
            'table_headers_mid' => esc_html__('Price', 'exchange-rates'),
            'table_headers_previous_close' => esc_html__('Previous Close', 'exchange-rates'),
            'table_headers_changes' => esc_html__('Changes', 'exchange-rates'),
            'table_headers_name_show' => null,
            'table_headers_mid_show' => null,
            'table_headers_code_show' => false,
            'table_headers_previous_close_show' => false,
            'table_headers_changes_show' => false,
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
