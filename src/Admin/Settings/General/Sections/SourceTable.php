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
            'table_open' => '<table data-rows="5" data-display="hide" id="exchange-rates-source-table-1" class="exchange-rates-widgets-table widefat striped">',
        ]);
        $table->set_heading([
            ['data' => '', 'width' => '1%'],
            esc_html__('Item', 'exchange-rates'),
            esc_html__('ID', 'exchange-rates'),
            esc_html__('Base', 'exchange-rates'),
            esc_html__('Last Update', 'exchange-rates'),
        ]);

        $get_sources = Sources::get_list();
        $data_sources = DataSources::getInstance();
        $sources = $data_sources->get_sources_data();

        foreach ($sources['data'] as $key => $value) {
            $table->add_row([
                '<img width="16" height="16" alt="'.esc_attr($value['source']).'" src="'.plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/circular/'.esc_attr($value['country']).'.svg" />',
                (isset($get_sources[$value['source']])) ? esc_attr($get_sources[$value['source']]['name']) : esc_attr($value['source']),
                '<code>'.esc_html($value['source']).'</code>',
                '<code>'.esc_html($value['base']).'</code>',
                esc_html(get_date_from_gmt($value['lastupdate'], 'Y-m-d H:i:s')),
            ]);
        }

        $html = $table->generate();

        echo '<div class="table-responsive">',$html,'</div><p><button type="button" data-id="exchange-rates-source-table-1" class="button show-more-table">',esc_html__('Show More/Less', 'exchange-rates'),'</button></p>';
    }
}
