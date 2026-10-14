<?php

namespace Dejurin\ExchangeRates\Service;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Models\DataSources;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Request\Request;

class UpdateDataSources
{
    public static $rates_option_name = Plugin::PLUGIN_SLUG.'_rates';
    public static $req_rates = null;
    private static $rates_api_uri = 'https://api-bank.fex.to/';

    public static function update($force_source_id = null)
    {
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());

        // Update sources
        $get_data_sources = DataSources::getInstance();
        // $sources = $get_data_sources->get_sources_data();
        $source_id = $settings['source_id'];

        if (!is_null($force_source_id)) {
            $source_id = $force_source_id;
        }

        if (!isset(Sources::get_list()[$source_id])) {
            $settings['rates_available'] = false;
            update_option(Settings::$option_name, $settings);

            self::$req_rates = [
                'status' => false,
                'data' => null,
                'error' => 'Unknown exchange-rate source.',
            ];

            return false;
        }

        $req = new Request(self::$rates_api_uri.'rates/'.$source_id.'.json');
        self::$req_rates = $req->data();

        if (self::$req_rates['status']) {
            $payload_error = RatesData::validate_payload(self::$req_rates['data']);
            if ('' !== $payload_error) {
                self::$req_rates['status'] = false;
                self::$req_rates['error'] = $payload_error;
            }
        }

        $settings['rates_available'] = self::$req_rates['status'];

        if (self::$req_rates['status']) {
            update_option(self::$rates_option_name, self::$req_rates['data']);
            $settings['base_currency'] = self::$req_rates['data']['base'];
            $settings['update_timestamp'] = time();
        }

        // $result = 
        update_option(Settings::$option_name, $settings);

        return self::$req_rates['status'];
    }
}
