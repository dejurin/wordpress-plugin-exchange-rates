<?php

namespace Dejurin\ExchangeRates\Shortcodes;

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\CurrencyConverter as Service_CurrencyConverter;

class CurrencyConverter
{
    public $parameters;
    public $settings;
    public $default_attr = [];

    public const BADGE_SLUG = 'shortcode-'.Plugin::PLUGIN_SLUG.'-currency-converter';

    public function __construct()
    {
        add_shortcode(Plugin::PLUGIN_SLUG.'_currency-converter', [$this, 'shortcode']);
        // Default
        $this->default_attr = [
            'title' => 'title',
            'amount' => 1,
            'from' => 'USD',
            'to' => 'EUR',
            'code' => false,
            'border' => false,
            'after' => false,
            'symbol' => false,
            'id' => time(),
        ];
    }

    public function shortcode($attr = [])
    {
        $obj = new Service_CurrencyConverter();
        $obj->parameters = (isset($attr) && is_array($attr)) ? array_merge($this->default_attr, $attr) : $this->default_attr;

        return '<div class="'.Plugin::PLUGIN_SLUG.'">'.$obj->get_html_widget($obj->parameters['id']).'</div>';
    }
}
