<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\CurrencySymbols;
use Dejurin\ExchangeRates\Models\Dev;
use Dejurin\ExchangeRates\Models\Emoji;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\PHPTableGenerate;

class CurrencyTable
{
    public $parameters;
    public $settings;
    public $table;
    const WIDGET_SLUG = 'widget-'.Plugin::PLUGIN_SLUG.'-currency-table';
	/** Abbreviate only default headings; preserve saved custom labels. */
	private function history_heading( $label, $default, $short ) {
		$heading = array( 'data' => $label, 'class' => 'text-right' );
		if ( $label === $default ) {
			$heading['aria-label'] = esc_attr( $label );
			$heading['data'] = '<span class="exchange-rates-table-full-title">' . esc_html( $label ) . '</span>'
				. '<abbr class="exchange-rates-table-short-title" title="' . esc_attr( $label ) . '" aria-hidden="true">' . esc_html( $short ) . '</abbr>';
		}
		return $heading;
	}

	/** Render regular and base-currency labels with the same size-dependent layout. */
	private function render_label( $title, $region, $flag ) {
		$type = $this->parameters['flag_type'];
		$width = max( 16, min( 64, (int) $this->parameters['flag_size'] ) );
		$height = 'rectangular' === $type ? $width * 0.75 : $width;
		$show_region = ! empty( $this->parameters['region'] );
		$classes = 'd-flex exchange-rates-table-label exchange-rates-table-hybrid';
		if ( $show_region ) {
			$classes .= ' exchange-rates-table-region';
			if ( 'none' !== $type && $width < 40 ) {
				$classes .= ' exchange-rates-table-stacked';
			}
		}
		$html = '<div class="' . esc_attr( $classes ) . '">';
		if ( 'none' !== $type ) {
			if ( 'emoji' === $type ) {
				$emoji = Emoji::get_list();
				$src = plugin_dir_url( $GLOBALS['dejurin_exchange_rates']->plugin_path ) . 'assets/flags/emoji/' . strtolower( $emoji[ $flag ] ) . '.svg';
			} else {
				$src = plugin_dir_url( $GLOBALS['dejurin_exchange_rates']->plugin_path ) . 'assets/flags/' . rawurlencode( $type ) . '/' . rawurlencode( strtolower( $flag ) ) . '.svg';
			}
			$html .= '<img loading="lazy" style="width:' . esc_attr( $width ) . 'px!important;height:' . esc_attr( $height ) . 'px!important" src="' . esc_url( $src ) . '" alt="' . esc_attr( $title ) . '">';
		}
		$text_class = $this->parameters['full_width'] ? 'text-element w-100' : 'text-element';
		$html .= '<div class="d-flex-wrap"><div class="' . esc_attr( $text_class ) . '"><div class="text-truncate">' . esc_html( $title ) . '</div></div>';
		if ( $show_region ) {
			$html .= '<div class="' . esc_attr( $text_class ) . ' exchange-rates-region-text"><div class="text-truncate small">' . esc_html( $region ) . '</div></div>';
		}
		return $html . '</div></div>';
	}

    public function get_html_widget($widget_number)
    {
        $err = true;
        $settings = get_option(Settings::$option_name, []);
        $this->settings = wp_parse_args($settings, Settings::get_defaults());
        $rates = RatesData::get();
        if ( ! RatesData::has_current_rates( $rates ) ) {
            return Dev::rates_unavailable();
        }
        $has_previous_rates = RatesData::has_previous_rates($rates);
        $show_previous_close = !empty($this->parameters['table_headers_previous_close_show']) && $has_previous_rates;
        $show_changes = !empty($this->parameters['table_headers_changes_show']) && $has_previous_rates;
        // Retained rates keep their own source, even after a failed selection change.
        $source_id = RatesData::get_source($rates, $this->settings['source_id']);
        $last_currency = null;

        $flag_types = ['none', 'rectangular', 'square', 'circular', 'emoji'];
        if (!isset($this->parameters['flag_type']) || !in_array($this->parameters['flag_type'], $flag_types, true)) {
            $this->parameters['flag_type'] = 'rectangular';
        }

        $this->table = new PHPTableGenerate();

        // Header
        if ((!empty($this->parameters['table_headers_name']) || !empty($this->parameters['table_headers_mid'])) && $this->parameters['table_headers_show']) {
            $heading = [];
            $heading[0] = $this->parameters['table_headers_name'];

            if ($this->parameters['table_headers_code_show']) {
                $heading[1] = $this->parameters['table_headers_code'];
            }

            $heading[2]['data'] = $this->parameters['table_headers_mid']; // .($this->parameters['inverse'] ? '' : ' ('.$this->parameters['base_currency'].')')
            $heading[2]['class'] = 'text-right';

            if ($show_previous_close) {
                $heading[3] = $this->history_heading( $this->parameters['table_headers_previous_close'], __( 'Previous Close', 'exchange-rates' ), __( 'Prev.', 'exchange-rates' ) );
            }

            if ($show_changes) {
                $heading[4] = $this->history_heading( $this->parameters['table_headers_changes'], __( 'Changes', 'exchange-rates' ), __( 'Δ %', 'exchange-rates' ) );
            }

            $this->table->set_heading($heading);
        }

        $get_currencies = Currencies::get_list();

        if (!is_array($this->parameters['currency_list'])) {
            $this->parameters['currency_list'] = array_filter(explode(',', $this->parameters['currency_list']), 'strlen');
        }

        foreach ($this->parameters['currency_list'] as $currency_code) {
            $currency_code = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $currency_code));
            if (!preg_match('/^[A-Z]{2,6}$/', $currency_code) || !isset($get_currencies[$currency_code])) {
                continue;
            }

            $this->parameters['reverse'] = 'currencyrate' === $source_id;
            $currency = new Currency($this->parameters, $currency_code);
            if ($currency->is_available()) {
                $err = false;
                $last_currency = $currency;
                $output_data = [];
                $currency_name = $get_currencies[$currency_code]['name'];
                $currency_region = $get_currencies[$currency_code]['region'];
                $currency_title = $this->parameters['code'] ? $currency_code : $currency_name;

                $trend = $currency->get_trend();
                $change_visible = $trend && $currency->get_rate_format(0, true) !== $currency->get_rate_format(1, true);
                $percentage = $currency->get_change_percentage();
                $percentage_text = html_entity_decode($percentage, ENT_QUOTES, 'UTF-8');
                $change_label = $currency->has_previous_rate() ? __('No change.', 'exchange-rates') : __('Previous rate unavailable.', 'exchange-rates');
                if ($trend) {
                    /* translators: %s: Unsigned, localized percentage. */
                    $change_label = sprintf(1 === $trend ? __('Increase: %s', 'exchange-rates') : __('Decrease: %s', 'exchange-rates'), $percentage_text);
                }
                $hidden_label = $trend ? __('Change is below display precision.', 'exchange-rates') : $change_label;
                $visible_label = $change_visible ? $change_label : $hidden_label;
                $trend_name = (1 === $trend) ? 'up' : 'down';
                $trend_src = $trend ? plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/img/'.$trend_name.'.svg' : '';
                $class_trend = $change_visible ? ((1 === $trend) ? ' table-success' : ' table-danger') : '';
                $svg_trend = $change_visible ? '<img class="exchange-rates-trend" width="14" height="14" alt="'.$trend_name.'" src="'.esc_url($trend_src).'" />' : '';

                $output_data[0]['data'] = $this->render_label($currency_title, $currency_region, $get_currencies[$currency_code]['flag']);
                $output_data[0]['td'] = 'th';
                $output_data[0]['scope'] = 'row';

                if ($this->parameters['table_headers_code_show']) {
                    $output_data[1] = ['data' => $currency_code];
                }

                $symbol = '';
                if (isset($this->parameters['symbol']) && $this->parameters['symbol']) {
                    $symbol = CurrencySymbols::get_list($this->parameters['inverse'] ? $this->parameters['base_currency'] : $currency_code);
                }
                $pre = $symbol;
                $after = '';

                if (isset($this->parameters['after']) && $this->parameters['after']) {
                    $after = $pre;
                    $pre = '';
                }

                $output_data[2] = [
                    'data' => $pre.$currency->get_rate_format(0, true).$after.$svg_trend,
                    'data-rate' => $currency->get_rate(0),
                    'data-symbol' => $symbol,
                    'class' => 'text-right',
                    'data-previous-rate' => $currency->has_previous_rate() ? esc_attr($currency->get_rate(1)) : '',
                    'data-change-trend' => (int) $trend,
                    'data-change-percent' => esc_attr($percentage_text),
                    'data-change-label' => esc_attr($change_label),
                    'data-change-hidden-label' => esc_attr($hidden_label),
                    'data-trend-src' => esc_url($trend_src),
                    'title' => esc_attr($visible_label),
                ];

                $output_data[2]['data-currency'] = esc_attr($currency_code);

                if ($show_previous_close) {
                    $output_data[3] = [
                        'data' => $currency->has_previous_rate() ? $currency->get_rate_format(1, true) : '&ndash;',
                        'data-rate' => $currency->get_rate(1),
                        'data-currency' => esc_attr($currency_code),
                        'data-rate-period' => 'previous',
                        'data-symbol' => $symbol,
                        'class' => 'text-right',
                    ];
                }


                if ($show_changes) {
                    $output_data[4] = [
                        'data' => $change_visible ? $percentage : '&ndash;',
                        'class' => 'text-right'.$class_trend,
                        'data-change' => '1',
                        'aria-label' => esc_attr($visible_label),
                        'title' => esc_attr($visible_label),
                    ];
                }

                $this->table->add_row($output_data);
            }
        }

        if ($err) {
            return '<b data-exchange-rates-version="'.esc_attr( Plugin::VERSION ).'">'.Plugin::NAME.'</b> '.esc_html__('Error: Check parameters of widget or shortcode. Probably you changed the source of currency rates, where there was not the desired currency that you selected before.', 'exchange-rates');
        }

        if ($this->parameters['base_show']) {
            $base_currency_code = strtoupper(trim((string) $this->parameters['base_currency']));
            $output_data = [];
            if (!isset($get_currencies[$base_currency_code])) {
                $this->parameters['base_show'] = false;
            } else {
            $base_currency_name = $get_currencies[$base_currency_code]['name'];
            $base_currency_title = $this->parameters['code'] ? $base_currency_code : $base_currency_name;

            $output_data[0]['data'] = $this->render_label($base_currency_title, $get_currencies[$base_currency_code]['region'], $get_currencies[$base_currency_code]['flag']);
            $output_data[0]['td'] = 'th';
            $output_data[0]['scope'] = 'row';
            $output_data[0]['class'] = 'active';

            if ($this->parameters['table_headers_code_show']) {
                $output_data[1] = [
                    'data' => $base_currency_code,
                    'class' => 'active',
                ];
            }

            $amount_template = $this->parameters['amount_active'] ? '<input id="'.esc_attr(wp_unique_id('exchange-rates-table-amount-')).'" aria-label="'.esc_attr__('Amount', 'exchange-rates').'" value="%1$s" />' : '%1$s';

            $output_data[2] = [
                'data' => sprintf($amount_template, $last_currency->get_amount()),
                'class' => 'text-right active',
            ];

            // Complete only the visible history columns; the label mode does not change the grid.
            $history_columns = (int) $show_previous_close + (int) $show_changes;
            if ($history_columns > 0) {
                $output_data[3] = [
                    'data' => '',
                    'colspan' => $history_columns,
                    'class' => 'active',
                ];
            }

            unset($output_data[4]);
            $this->table->add_row($output_data);
            }
        }

        $fmt = Currency::get_fmt($this->settings['currency_format']);

        $class = $this->parameters['full_width'] ? 'exchange-rates-table-full' : '';
        $class .= ($this->parameters['border'] ? ' table-border' : '');
        $class .= ($this->parameters['border_hori'] ? ' table-border-hori' : '');
        $class .= ($this->parameters['border_vert'] ? ' table-border-vert' : '');

        $template = [
            'heading_cell_start' => '<th scope="col">',
            'table_open' => '<div class="cr-'.Plugin::PLUGIN_SLUG.'" data-exchange-rates-version="'.esc_attr( Plugin::VERSION ).'"><div class="table-responsive '.self::WIDGET_SLUG.( ( $show_previous_close || $show_changes ) ? ' exchange-rates-table-history' : '' ).'"><table '
                            .'class="'.esc_attr(trim($class)).'" '
                            .'data-base-currency="'.esc_attr($this->parameters['base_currency']).'" data-inverse="'.(!empty($this->parameters['inverse']) ? 'true' : 'false').'" data-amount="'.esc_attr($this->parameters['amount']).'" '
                            .'data-decimals="'.esc_attr($this->settings['decimals']).'" '
                            .'data-decimal-point="'.esc_attr($fmt['decimal_point']).'" '
                            .'data-thousands-sep="'.esc_attr($fmt['thousands_sep']).'" '
                            .(($this->parameters['after']) ? ' data-after="true"' : '')
                            .'>',
            'table_close' => '</table></div></div>',
        ];

        $this->table->set_template($template);

        $html = $this->table->generate();
        $html .= Dev::caption(
            $this->parameters,
            $last_currency->get_date(),
            $widget_number,
            self::WIDGET_SLUG,
            $source_id,
            0
        );

        return $html;
    }
}
