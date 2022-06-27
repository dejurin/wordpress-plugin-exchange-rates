<?php

namespace Dejurin\ExchangeRates\Shortcodes;

use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Currency;
use Dejurin\ExchangeRates\Models\CurrencySymbols;
use Dejurin\ExchangeRates\Models\Dev;
use Dejurin\ExchangeRates\Models\Emoji;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\Color;
use Dejurin\ExchangeRates\Service\Tools;

class Badge
{
    public $default_attr = [
        'amount' => 1,
        'base_currency' => 'USD',
        'currency_list' => 'EUR',
        'base_show' => false,
        'inverse' => false,
        'decimals' => 4,
        'flag_type' => 'emoji',
        'color' => '#eeeeee',
        'after' => false,
    ];

    public const BADGE_SLUG = 'shortcode-'.Plugin::PLUGIN_SLUG.'-badge';

    public function __construct()
    {
        add_shortcode(Plugin::PLUGIN_SLUG.'-badge', [$this, 'shortcode']);
    }

    protected function img($flag_type, $currency)
    {
        $w = 24;
        $h = ('rectangular' === $flag_type) ? ($w / 4 * 3) : $w;

        $img_template = '<img loading="lazy" src="%1$s" width="%2$s" height="%3$s" alt="%4$s" />&nbsp;';
        $img_template_src = plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path).'assets/flags/'.$flag_type.'/%1$s.svg';
        $line = '';

        if ('emoji' === $flag_type) {
            $emoji = Emoji::get_list();
            $line = $emoji[$currency['flag']].'&nbsp;';
        } elseif ('none' !== $flag_type) {
            $line = sprintf(
                $img_template,
                sprintf($img_template_src, strtolower($currency['flag'])),
                $w,
                $h,
                'title'
            );
        }

        return $line;
    }

    public function shortcode($attr)
    {
        $caption = null;
        $currency = null;
        $result = '';
        $err = true;

        if (is_array($attr)) {
            $attr = array_merge($this->default_attr, $attr);
        } else {
            $attr = $this->default_attr;
        }

        $attr['base_show'] = false !== $attr['base_show'];
        $attr['inverse'] = false !== $attr['inverse'];

        $rgb = Color::HTMLToRGB($attr['color']);
        $hsl = Color::RGBToHSL($rgb);

        if (array_key_exists('currency_list', $attr) && !empty($attr['currency_list'])) {
            $currency_list = explode(',', $attr['currency_list']);
            $get_currencies = Currencies::get_list();

            if (is_array($currency_list) && !empty($currency_list)) {
                $parameters = Tools::filter_keys_allowed_list($attr, ['base_currency', 'amount', 'base_show', 'code', 'currency_format', 'inverse', 'decimals', 'symbol', 'after', 'color', 'flag_type']);

                foreach ($currency_list as $code) {
                    $currency = new Currency($parameters, $code);
                    if ($currency->is_available() && isset($get_currencies[$code])) {
                        $get_currency = $get_currencies[$code];

                        if ($this->settings['source_id'] === 'currencyrate') {
                            $symbol = CurrencySymbols::get_list($this->parameters['inverse'] ? $parameters['base_currency'] : $code);
                        } else {
                            $symbol = CurrencySymbols::get_list($this->parameters['inverse'] ? $code : $parameters['base_currency']);
                        }
                        
                        $pre = $symbol;
                        $after = '';

                        if ($parameters['after']) {
                            $after = $pre;
                            $pre = '';
                        }

                        $base_currency = ($parameters['base_show']) ? ((isset($parameters['code'])) ? $parameters['base_currency'] : $get_currencies[$parameters['base_currency']]['name']).'/' : '';
                        $template = '<div class="badge-leaders"><span style="background-color:%1$s;color:%2$s">%3$s'.$base_currency.'%4$s</span><span style="background-color:%1$s;color:%2$s">%5$s%6$s%7$s</span></div>';
                        $result .= sprintf(
                                $template,
                                $parameters['color'],
                                $hsl->lightness > 200 ? '#333333' : '#ffffff',
                                $this->img($parameters['flag_type'], $get_currency),
                                (isset($parameters['code']) ? $code : $get_currency['name']),
                                $pre,
                                $currency->get_rate_format(0, true, true),
                                $after,
                            );
                        $err = false;
                    }
                }
            }
        }

        if ($currency) {
            $caption = Dev::caption(
                $parameters,
                $currency->get_date(),
                time(),
                self::BADGE_SLUG,
                $currency->get_source_id()
            );
        } else {
            $err = true;
        }

        if ($err) {
            $result = __(Plugin::NAME.': error plugin. Check the attributes of the shortcode.', Plugin::PLUGIN_SLUG);
        }

        return '<div class="exchange-rates shortcode-exchange-rates-badge">'.$result.$caption.'</div>';
    }
}
