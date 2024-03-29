<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Request\Request;

class DataSources
{
    public static $transient_option_name = Plugin::PLUGIN_SLUG.'_sources';
    private static $instance;

    private $sources_url = 'https://api-bank.fex.to/sources.json';
    private $sources_data = null;

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    public function get_sources_data()
    {
        $this->sources_data = get_transient(self::$transient_option_name);

        if (!$this->validate_sources_data() || empty($this->sources_data)) {
            $this->fetch_source_data();
            set_transient(self::$transient_option_name, $this->sources_data, HOUR_IN_SECONDS);
        }

        return $this->sources_data;
    }

    private function validate_sources_data()
    {
        if (
            isset($this->sources_data['status'])
            &&
            true === $this->sources_data['status']
            &&
            !empty($this->sources_data['data'])
        ) {
            return true;
        }

        return false;
    }

    private function fetch_source_data()
    {
        $req = new Request($this->sources_url);
        $this->sources_data = $req->data();
    }
}
