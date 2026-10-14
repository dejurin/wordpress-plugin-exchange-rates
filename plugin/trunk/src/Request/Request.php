<?php

namespace Dejurin\ExchangeRates\Request;

if (!defined('ABSPATH')) {
    exit;
}

// use \Dejurin\ExchangeRates\Plugin;

class Request
{
    private $api_url;

    public function __construct($api_url)
    {
        $this->api_url = $api_url;
    }

    public function data()
    {
        $response = wp_remote_get($this->api_url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            return [
                'status' => false,
                'data' => null,
                'error' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return [
                'status' => false,
                'data' => null,
                'error' => sprintf('HTTP response code %s.', $code),
            ];
        }

        $body = wp_remote_retrieve_body($response);
        if ('' === $body) {
            return [
                'status' => false,
                'data' => null,
                'error' => 'Empty response body.',
            ];
        }

        $data = json_decode($body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return [
                'status' => false,
                'data' => null,
                'error' => json_last_error_msg(),
            ];
        }

        return [
            'status' => true,
            'data' => $data,
            'error' => null,
        ];
    }
}
