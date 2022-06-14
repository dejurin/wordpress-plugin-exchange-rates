<?php

namespace Dejurin\ExchangeRates\Service;

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\Dev;
use Dejurin\ExchangeRates\Models\Emoji;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\PHPTableGenerate;

class CurrencyTable
{
    public $parameters;
    public $table;

    public function get_table($widget_number)
    {
        $this->table = new PHPTableGenerate();

        // Header
        if ((!empty($this->parameters['table_headers_name']) || !empty($this->parameters['table_headers_mid'])) && $this->parameters['table_headers_show']) {
            $heading = [];
            $heading[0] = $this->parameters['table_headers_name'];

            if ($this->parameters['table_headers_code_show']) {
                $heading[1] = $this->parameters['table_headers_code'];
            }

            $heading[2] = $this->parameters['table_headers_mid'].($this->parameters['inverse'] ? ' ('.$this->parameters['base_currency'].')' : '');

            if ($this->parameters['table_headers_previous_close_show']) {
                $heading[3] = $this->parameters['table_headers_previous_close'];
            }

            if ($this->parameters['table_headers_changes_show']) {
                $heading[4] = $this->parameters['table_headers_changes'];
            }

            $this->table->set_heading($heading);
        }

        $get_currencies = Currencies::get_currencies();

        $w = $this->parameters['flag_size'];
        $h = $this->parameters['flag_size'];

        if ('rectangular' === $this->parameters['flag_type']) {
            $ratio = $this->parameters['flag_size'] / 4;
            $h = $ratio * 3;
        }

        $w100 = $this->parameters['full_width'] ? 'text-element w-100' : 'text-element';

        $img_template = '<img loading="lazy" width="%1$s" height="%2$s" src="%3$s" alt="%4$s">&nbsp;<div class="d-flex-wrap"><div class="'.$w100.'"><div class="text-truncate">%4$s</div></div>';
        $region_template = '<div class="break"></div><div class="'.$w100.'"><div class="text-truncate small">%1$s</div></div>';
        $template = '%1$s&nbsp;<div class="d-flex-wrap"><div class="'.$w100.'"><div class="text-truncate">%2$s</div></div>';
        $start_template = '<div class="d-flex">';
        $end_template = '</div></div>';
        $img_src_template = plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/'.$this->parameters['flag_type'].'/%1$s.svg';

        foreach ($this->parameters['currency_list'] as $currency_code) {
            $currency_obj = new Currency($this->parameters, $currency_code);

            if ($currency_obj->is_available()) {
                $currency_name = $get_currencies[$currency_code]['name'];
                $currency_region = $get_currencies[$currency_code]['region'];
                $currency_title = $this->parameters['code'] ? $currency_code : $currency_name;
                $class_trend = '';

                $svg_trend = '<img src="'.plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/img/%1$s.png">';

                if (1 === $currency_obj->get_trend()) {
                    $svg_trend = sprintf($svg_trend, 'up');
                } elseif (-1 === $currency_obj->get_trend()) {
                    $svg_trend = sprintf($svg_trend, 'down');
                } else {
                    $svg_trend = '';
                }

                if (1 === $currency_obj->get_trend()) {
                    $class_trend = ' table-success';
                } elseif (-1 === $currency_obj->get_trend()) {
                    $class_trend = ' table-danger';
                }

                $text_changes = sprintf('%1$s&percnt;', $currency_obj->get_change_percentage());

                $output_data[0]['data'] = $start_template;

                if ('emoji' === $this->parameters['flag_type']) {
                    $emoji = Emoji::get_list();
                    $output_data[0]['data'] .= sprintf($template, $emoji[$get_currencies[$currency_code]['flag']], $currency_title);
                } elseif ('none' !== $this->parameters['flag_type']) {
                    $output_data[0]['data'] .= sprintf(
                        $img_template,
                        $w,
                        $h,
                        sprintf($img_src_template, strtolower($get_currencies[$currency_code]['flag'])),
                        $currency_title);
                } else {
                    $output_data[0]['data'] .= sprintf($template, '', $currency_title);
                }

                if ($this->parameters['region']) {
                    $output_data[0]['data'] .= sprintf($region_template, $currency_region);
                }

                $output_data[0]['data'] .= $end_template;
                $output_data[0]['td'] = 'th';
                $output_data[0]['scope'] = 'row';

                if ($this->parameters['table_headers_code_show']) {
                    $output_data[1] = ['data' => $currency_code];
                }

                $output_data[2] = [
                    'data' => $currency_obj->get_rate_format(0, true).$svg_trend,
                    'data-rate' => $currency_obj->get_rate(0),
                ];

                if (0 !== $currency_obj->get_trend()) {
                    $output_data[2]['title'] = $text_changes;
                }

                if ($this->parameters['table_headers_previous_close_show']) {
                    $output_data[3] = [
                        'data' => $currency_obj->get_rate_format(1, true),
                        'data-rate' => $currency_obj->get_rate(1),
                        'class' => 'text-right',
                    ];
                }

                if ($this->parameters['table_headers_changes_show']) {
                    $output_data[4] = [
                        'data' => (0 === $currency_obj->get_trend()) ? '&mdash;' : $text_changes,
                        'class' => 'text-right'.$class_trend,
                        'title' => $currency_obj->get_change(),
                    ];
                }

                $this->table->add_row($output_data);
            }
        }

        if ($this->parameters['base_show']) {
            $base_currency_code = $this->parameters['base_currency'];
            $base_currency_name = $get_currencies[$this->parameters['base_currency']]['name'];
            $base_currency_title = $this->parameters['code'] ? $base_currency_code : $base_currency_name;

            $output_data[0]['data'] = $start_template;

            if ('emoji' === $this->parameters['flag_type']) {
                $emoji = Emoji::get_list();
                $output_data[0]['data'] .= sprintf($template, $emoji[$get_currencies[$base_currency_code]['flag']], $base_currency_title);
            } elseif ('none' !== $this->parameters['flag_type']) {
                $output_data[0]['data'] .= sprintf(
                    $img_template,
                    $w,
                    $h,
                    sprintf($img_src_template, strtolower($get_currencies[$base_currency_code]['flag'])),
                    $base_currency_title
                );
            } else {
                $output_data[0]['data'] .= sprintf($template, '', $base_currency_title);
            }

            if ($this->parameters['region']) {
                $output_data[0]['data'] .= sprintf($region_template, $get_currencies[$base_currency_code]['region']);
            }

            $output_data[0]['data'] .= $end_template;
            $output_data[0]['td'] = 'th';
            $output_data[0]['scope'] = 'row';
            $output_data[0]['class'] = 'active';

            if ($this->parameters['table_headers_code_show']) {
                $output_data[1] = [
                    'data' => $base_currency_code,
                    'class' => 'active',
                ];
            }

            $amount_template = $this->parameters['amount_active'] ? '<input value="%1$s" />' : '%1$s';

            $output_data[2] = [
                'data' => sprintf($amount_template, Currency::for_format(1 * $this->parameters['amount'], $this->parameters, $this->parameters['decimals'])),
                'class' => 'text-right active',
            ];
            $output_data[3] = [
                'data' => '',
                'colspan' => 2,
                'class' => 'active',
            ];

            unset($output_data[4]);
            $this->table->add_row($output_data);
        }

        $fmt = Currency::get_fmt($this->parameters['currency_format']);

        $template = [
            'heading_cell_start' => '<th scope="col">',
            'table_open' => '<div class="table-responsive"><table data-decimals="'.$this->parameters['decimals'].'" data-decimal-point="'.$fmt['decimal_point'].'" data-thousands-sep="'.$fmt['thousands_sep'].'">',
            'table_close' => '</table></div>',
        ];

        $get_sources = Sources::get_sources();

        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());

        $source_title = $get_sources[$settings['source_id']];

        $this->table->set_template($template);
        $html = $this->table->generate();
        $html .= Dev::caption(
            $this->parameters,
            $source_title['name'],
            $currency_obj->get_date(),
            $widget_number
        );

        return $html;
    }
}
