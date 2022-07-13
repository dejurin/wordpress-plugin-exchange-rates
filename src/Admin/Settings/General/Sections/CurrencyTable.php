<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections;

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\PHPTableGenerate;

class CurrencyTable
{
    public static function init()
    {
        self::render();
    }

    public static function render()
    {
        $table = new PHPTableGenerate();
        $table->set_template([
            'table_open' => '<table data-rows="5" data-display="hide" id="exchange-rates-currency-table-1" class="exchange-rates-widgets-table widefat striped">',
        ]);
        $table->set_heading([
            ['data' => '', 'width' => '1%'],
            esc_html__('Currency', 'exchange-rates'),
            esc_html__('ISO code', 'exchange-rates'),
            esc_html__('Rate', 'exchange-rates'),
            esc_html__('Close', 'exchange-rates'),
            esc_html__('Region', 'exchange-rates'),
        ]);

        $get_currencies = Currencies::get_list();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');

        $close = isset($rates['data'][1]);

        foreach ($get_currencies as $key => $value) {
            if (isset($rates['data'][0]['rates'][$key])) {
                $table->add_row([
                    '<img width="16" height="16" alt="'.esc_attr($key).'" src="'.plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/circular/'.esc_attr(strtolower($value['flag'])).'.svg" />',
                    esc_attr($value['name']),
                    '<code>'.esc_attr($key).'</code>',
                    esc_attr($rates['data'][0]['rates'][$key]),
                    $close ? ($rates['data'][1]['rates'][$key]) : esc_attr('&ndash;'),
                    esc_attr($value['region']),
                ]);
            }
        }

        echo '<div class="table-responsive">',$table->generate(),'</div><p><button type="button" data-id="exchange-rates-currency-table-1" class="button show-more-table">',esc_html__('Show More/Less', 'exchange-rates'),'</button></p>';
    }
}
