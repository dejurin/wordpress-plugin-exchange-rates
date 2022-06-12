<?php

namespace Dejurin\ExchangeRates\Service;

use Dejurin\ExchangeRates\Models\DataSources;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Request\Request;
use Dejurin\ExchangeRates\Plugin;

class UpdateDataSources
{
    public static $req_rates = null;
    private static $rates_api_uri = 'https://api-bank.fex.to/';

    public static function update($show_warning_in_admin = false)
    {
        // Получаем полностью все настройки + дефолтные на 100%
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());

        $prov = DataSources::getInstance();
        $sources = $prov->get_sources_data();

        $req = new Request(self::$rates_api_uri.'rates/'.$settings['source_id'].'.json');
        self::$req_rates = $req->data();

        if (self::$req_rates['status']) {
            update_option(Plugin::PLUGIN_SLUG.'_rates', self::$req_rates['data']);
            $settings['rates_available'] = true;
        }

        $result = update_option(Settings::$option_name, $settings);

        return json_encode($settings);
    }
}
