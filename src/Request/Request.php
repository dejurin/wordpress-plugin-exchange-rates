<?php

namespace Dejurin\ExchangeRates\Request;

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
        $error = is_wp_error($response);
        $body = null;
        $error_message = null;
        if (is_array($response) && !$error) {
            // $headers = $this->response['headers'];
            $body = json_decode($response['body'], true);
        } else {
            $error_message = $response->get_error_message();
        }

        return [
            'status' => !$error,
            'data' => $body,
            'error' => $error_message,
        ];
    }
}
