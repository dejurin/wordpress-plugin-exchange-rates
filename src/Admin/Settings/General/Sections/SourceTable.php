<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General\Sections;

use Dejurin\ExchangeRates\Models\DataSources;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\PHPTableGenerate;

class SourceTable
{
    public static function init()
    {
        self::render();
    }

    public static function render()
    {
        $table = new PHPTableGenerate();
        $table->set_template([
            'table_open' => '<table data-rows="3" data-display="hide" id="exchange-rates-source-table-1" class="exchange-rates-widgets-table widefat striped">',
        ]);
        $table->set_heading([
            ['data' => '', 'width' => '1%'],
            __('Item', Plugin::PLUGIN_SLUG),
            __('ID', Plugin::PLUGIN_SLUG),
            __('Base Currency', Plugin::PLUGIN_SLUG),
            __('Last Update', Plugin::PLUGIN_SLUG),
        ]);

        $get_sources = Sources::get_sources();

        $prov = DataSources::getInstance();
        $sources = $prov->get_sources_data();

        foreach ($sources['data'] as $key => $value) {
            $table->add_row([
                '<img width="16" height="16" src="'.plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/circular/'.$value['country'].'.svg" />',
                (isset($get_sources[$value['source']])) ? $get_sources[$value['source']]['name'] : esc_html($value['source']),
                '<code>'.$value['source'].'</code>',
                '<code>'.$value['base'].'</code>',
                get_date_from_gmt($value['lastupdate'], 'Y-m-d H:i:s'),
            ]);
        }


        $html = $table->generate();

        echo $html,'<p><button type="button" data-id="exchange-rates-source-table-1" class="button show-more-table">',_e('Show More/Less', Plugin::PLUGIN_SLUG),'</button></p>';
    }
}
