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
            __('Currency', Plugin::PLUGIN_SLUG),
            __('ISO code', Plugin::PLUGIN_SLUG),
            __('Rate', Plugin::PLUGIN_SLUG),
            __('Close', Plugin::PLUGIN_SLUG),
            __('Region', Plugin::PLUGIN_SLUG),
        ]);

        $get_currencies = Currencies::get_currencies();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');


        foreach ($get_currencies as $key => $value) {
            if (isset($rates['data'][0]['rates'][$key])) {
                $table->add_row([
                    '<img width="16" height="16" src="'.plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/circular/'.strtolower($value['flag']).'.svg" />',
                    $value['name'],
                    '<code>'.$key.'</code>',
                    $rates['data'][0]['rates'][$key],
                    $rates['data'][1]['rates'][$key],
                    $value['region'],
                ]);
            }
        }

        $html = $table->generate();

        echo $html,'<p><button type="button" data-id="exchange-rates-currency-table-1" class="button show-more-table">',_e('Show More/Less', Plugin::PLUGIN_SLUG),'</button></p>';
    }
}
