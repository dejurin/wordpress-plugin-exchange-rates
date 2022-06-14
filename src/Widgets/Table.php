<?php

namespace Dejurin\ExchangeRates\Widgets;

use Dejurin\ExchangeRates\Models\Checkbox;
use Dejurin\ExchangeRates\Models\ColumnRate;
use Dejurin\ExchangeRates\Models\CurrencyFormat;
use Dejurin\ExchangeRates\Models\Flags;
use Dejurin\ExchangeRates\Plugin;

class Table extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            Plugin::PLUGIN_SLUG.'_table',
            __('Exchange Rates Table', Plugin::PLUGIN_SLUG),
            [
                'classname' => 'widget-'.Plugin::PLUGIN_SLUG.'-table',
                'description' => __('A table with currency rates.', Plugin::PLUGIN_SLUG),
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
            $instance['currency_list'] = str_replace(' ', '', $instance['currency_list']);
            $instance['currency_list'] = explode(',', trim($instance['currency_list'], ',')); // add trim comma

            if (!empty($instance['currency_list'])) {
                if (!empty($instance['base_currency'])) {
                    $table = new \Dejurin\ExchangeRates\Service\CurrencyTable();
                    $table->parameters = [
                        'amount' => (float) $instance['amount'],
                        'base_currency' => (string) $instance['base_currency'],
                        'currency_list' => (array) $instance['currency_list'],
                        'decimals' => (int) $instance['decimals'],
                        'flag_size' => (int) $instance['flag_size'],
                        'flag_type' => (string) $instance['flag_type'],
                        'currency_format' => (int) $instance['currency_format'],
                        'inverse' => (bool) $instance['inverse'],
                        'code' => (bool) $instance['code'],
                        'region' => (bool) $instance['region'],
                        'full_width' => (bool) $instance['full_width'],
                        'amount_active' => (bool) $instance['amount_active'],
                        'base_show' => (bool) $instance['base_show'],
                        'table_headers_show' => (bool) $instance['table_headers_show'],
                        'table_headers_name' => (string) $instance['table_headers_name'],
                        'table_headers_code' => (string) $instance['table_headers_code'],
                        'table_headers_mid' => (string) $instance['table_headers_mid'],
                        'table_headers_previous_close' => (string) $instance['table_headers_previous_close'],
                        'table_headers_changes' => (string) $instance['table_headers_changes'],
                        'table_headers_code_show' => (bool) $instance['table_headers_code_show'],
                        'table_headers_previous_close_show' => (bool) $instance['table_headers_previous_close_show'],
                        'table_headers_changes_show' => (bool) $instance['table_headers_changes_show'],
                    ];
                    echo $table->get_table($this->number);
                }
            }
        }

        echo $args['after_widget'];
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $this->_merge_instance_with_default_instance($new_instance);
        $instance_to_save['amount'] = (float) sanitize_text_field($instance['amount']);
        $instance_to_save['base_currency'] = (string) strtoupper(sanitize_text_field($instance['base_currency']));
        $instance_to_save['currency_list'] = (string) strtoupper(sanitize_text_field($instance['currency_list']));
        $instance_to_save['decimals'] = (int) sanitize_text_field($instance['decimals']);
        $instance_to_save['flag_size'] = (int) sanitize_text_field($instance['flag_size']);
        $instance_to_save['flag_type'] = (string) sanitize_text_field($instance['flag_type']);
        $instance_to_save['currency_format'] = (int) sanitize_text_field($instance['currency_format']);
        $instance_to_save['inverse'] = (bool) sanitize_text_field($instance['inverse']);
        $instance_to_save['code'] = (bool) sanitize_text_field($instance['code']);
        $instance_to_save['region'] = (bool) sanitize_text_field($instance['region']);
        $instance_to_save['full_width'] = (bool) sanitize_text_field($instance['full_width']);
        $instance_to_save['amount_active'] = (bool) sanitize_text_field($instance['amount_active']);
        $instance_to_save['base_show'] = true === (bool) sanitize_text_field($instance['base_show']) && !isset($new_instance['base_show']) ? false : true;
        // Table headers
        $instance_to_save['table_headers_name'] = (string) sanitize_text_field($instance['table_headers_name']);
        $instance_to_save['table_headers_code'] = (string) sanitize_text_field($instance['table_headers_code']);
        $instance_to_save['table_headers_mid'] = (string) sanitize_text_field($instance['table_headers_mid']);
        $instance_to_save['table_headers_previous_close'] = (string) sanitize_text_field($instance['table_headers_previous_close']);
        $instance_to_save['table_headers_changes'] = (string) sanitize_text_field($instance['table_headers_changes']);
        $instance_to_save['table_headers_previous_close_show'] = (bool) sanitize_text_field($instance['table_headers_previous_close_show']);
        $instance_to_save['table_headers_code_show'] = (bool) sanitize_text_field($instance['table_headers_code_show']);
        $instance_to_save['table_headers_changes_show'] = (bool) sanitize_text_field($instance['table_headers_changes_show']);
        $instance_to_save['table_headers_show'] = true === (bool) sanitize_text_field($instance['table_headers_show']) && !isset($new_instance['table_headers_show']) ? false : true;

        return $instance_to_save;
    }

    public function form($instance)
    {
        static $first = true;

        $instance = $this->_merge_instance_with_default_instance($instance);
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates'); ?>
    
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
 
		<p class="description"><?php _e('Amount multiplied by the rate.', Plugin::PLUGIN_SLUG); ?></p>
		<p><label for="<?php echo $this->get_field_id('base_currency'); ?>"><?php _e('Base currency:', Plugin::PLUGIN_SLUG); ?></label>
		<select class="plugin__currency__select-autocomplete" id="<?php echo $this->get_field_id('base_currency'); ?>" name="<?php echo $this->get_field_name('base_currency'); ?>">
			<?php
                $base_currency_list = array_keys($rates['data'][0]['rates']);

        foreach ($base_currency_list as $value) {
            printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($value),
                        selected($value, $instance['base_currency'], false),
                        esc_html($value)
                    );
        } ?>
		</select>
            </p>
		<p class="description"><?php _e('The currency in which will be settled other currencies.', Plugin::PLUGIN_SLUG); ?></p>
		<p>
            <label for="<?php echo $this->get_field_id('currency_list'); ?>"><?php _e('Currencies list:', Plugin::PLUGIN_SLUG); ?></label>
		    <input
                class="plugin__currency__autocomplete"
                data-autocomplete='<?php echo implode(',', $base_currency_list); ?>'
                id="<?php echo $this->get_field_id('currency_list'); ?>"
                name="<?php echo $this->get_field_name('currency_list'); ?>" 
                value="<?php echo esc_attr($instance['currency_list']); ?>"
                type="text">
            </p>
		<p class="description"><?php _e('The currencies which will be displayed in table. Separate by commas.', Plugin::PLUGIN_SLUG); ?></p>
    </fieldset>

        <fieldset style="padding:5px 15px;margin-bottom:15px">
        <legend><?php _e('Formatting', Plugin::PLUGIN_SLUG); ?></legend>
        
        <p>
            <label for="<?php echo $this->get_field_id('currency_format'); ?>"><?php _e('Currency format:', Plugin::PLUGIN_SLUG); ?></label>
            <select id="<?php echo $this->get_field_id('currency_format'); ?>" name="<?php echo $this->get_field_name('currency_format'); ?>">
                <?php
                    foreach (CurrencyFormat::get_list() as $key => $value) {
                        printf(
                            '<option value="%s"%s>%s</option>',
                            esc_attr($key),
                            selected($key, $instance['currency_format'], false),
                            esc_html($value['name'])
                        );
                    } ?>
            </select>
        </p>

        <p><label for="<?php echo $this->get_field_id('decimals'); ?>"><?php _e('Decimals:', Plugin::PLUGIN_SLUG); ?></label>

<input 
        id="<?php echo $this->get_field_id('decimals'); ?>"
        name="<?php echo $this->get_field_name('decimals'); ?>"
        type="range"
        step="1"
        min="0"
        max="7"
        value="<?php echo esc_attr($instance['decimals']); ?>">
        <span id="<?php echo $this->get_field_id('decimals'); ?>-show"><?php echo esc_attr($instance['decimals']); ?></span></p>


        </fieldset>

<fieldset style="padding:5px 15px;margin-bottom:15px">
        <legend><?php _e('Flag', Plugin::PLUGIN_SLUG); ?></legend>
        <p>
            <label for="<?php echo $this->get_field_id('flag_size'); ?>"><?php _e('Flag size:', Plugin::PLUGIN_SLUG); ?></label>
            <input 
                id="<?php echo $this->get_field_id('flag_size'); ?>"
                name="<?php echo $this->get_field_name('flag_size'); ?>"
                type="range"
                step="8"
                min="16"
                max="64"
                value="<?php echo esc_attr($instance['flag_size']); ?>">
            <span id="<?php echo $this->get_field_id('flag_size'); ?>-show"><?php echo esc_attr($instance['flag_size']); ?></span>px
        </p>
        <p>
            <label for="flag-type"><?php _e('Flag type:', Plugin::PLUGIN_SLUG); ?></label> 
            <?php foreach (Flags::get_types() as $item) {
                        echo sprintf('<input type="radio" id="flag-types-%1$s" name="%3$s" value="%1$s"%2$s><label for="flag-types-%1$s">%4$s</label>&nbsp;',
                esc_attr($item['value']),
                checked($item['value'], $instance['flag_type'], false),
                $this->get_field_name('flag_type'),
                $item['name']);
                    } ?>
        </p>
</fieldset>
<fieldset style="padding:5px 15px;margin-bottom:15px">
        <legend><?php _e('Options', Plugin::PLUGIN_SLUG); ?></legend>
        <?php foreach (Checkbox::get_list() as $key => $value) {
                        echo sprintf('<p><input type="checkbox" id="%1$s" name="%3$s" value="%1$s"%2$s><label for="%1$s">%4$s</label></p>',
                esc_attr($key),
                checked(true, $instance[$key], false),
                $this->get_field_name($key),
                $value);
                    } ?>
</fieldset>

		
        <fieldset style="padding:5px 15px">
        <legend><?php _e('Table headers', Plugin::PLUGIN_SLUG); ?></legend>

        <table><tbody>
            <tr><td scope="col" colspan="2">
            <?php echo sprintf('<input type="checkbox" id="%1$s" name="%3$s" value="%1$s"%2$s><label for="%1$s">%4$s</label>',
                esc_attr('table_headers_show'),
                checked(true, $instance['table_headers_show'], false),
                $this->get_field_name('table_headers_show'),
                __('Show table header', Plugin::PLUGIN_SLUG)); ?>
            </td></tr>
        
        <?php $i = 0;
        foreach (ColumnRate::get_сolumns() as $key => $value) {
            ++$i; ?>
        <tr>
        <td>
            <?php echo sprintf('<input type="checkbox" id="%1$s" name="%3$s" value="%1$s"%2$s %5$s><label for="%1$s">%4$s</label>',
                esc_attr('table_headers_'.$key.'_show'),
                checked(true, $instance['table_headers_'.$key.'_show'], false),
                $this->get_field_name('table_headers_'.$key.'_show'),
                $value,
                (!isset($instance['table_headers_'.$key.'_show'])) ? 'disabled checked readonly' : ''); ?>
            </td>
   


            <td>

                <input 
                    id="<?php echo $this->get_field_id('table_headers_'.$key); ?>"
                    name="<?php echo $this->get_field_name('table_headers_'.$key); ?>"
                    value="<?php echo esc_attr($instance['table_headers_'.$key]); ?>"
                    type="text">
            </td>
		</tr>

        <?php
        } ?>
        </tbody>
        </table>
        </fieldset>

		<?php $first = false;
    }

    private function _merge_instance_with_default_instance($instance)
    {
        $settings = [
            'amount' => 1,
            'base_currency' => 'USD',
            'currency_list' => 'EUR, GBP, CAD, AUD, JPY',
            'flag_size' => 16,
            'flag_type' => 'rectangular',
            'decimals' => 4,
            'currency_format' => 3,
            'inverse' => false,
            'code' => false,
            'region' => false,
            'full_width' => false,
            'amount_active' => false,
            'base_show' => true,
            'table_headers_show' => true,
            'table_headers_name' => __('Currency', Plugin::PLUGIN_SLUG),
            'table_headers_code' => __('Code', Plugin::PLUGIN_SLUG),
            'table_headers_mid' => __('Price', Plugin::PLUGIN_SLUG),
            'table_headers_previous_close' => __('Previous Close', Plugin::PLUGIN_SLUG),
            'table_headers_changes' => __('Changes', Plugin::PLUGIN_SLUG),
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
