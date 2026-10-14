<?php

namespace Dejurin\ExchangeRates\Shortcodes;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\CurrencyConverter as Service_CurrencyConverter;
use Dejurin\ExchangeRates\Service\Tools;

class CurrencyConverter
{
    public $parameters;
    public $settings;
    public $default_attr = [];

    const BADGE_SLUG = 'shortcode-'.Plugin::PLUGIN_SLUG.'-currency-converter';

    public function __construct()
    {
        add_shortcode(Plugin::PLUGIN_SLUG.'_currency-converter', [$this, 'shortcode']);

        // Defaults (static, predictable)
        $this->default_attr = [
            'title'  => 'title',
            'amount' => 1,
            'from'   => 'USD',
            'to'     => 'EUR',
            'code'   => false,
            'border' => false,
            'after'  => false,
            'symbol' => false,
            'id'     => '', // better: let shortcode generate stable id
        ];
    }

    private function parse_bool_attr($attr, $name, $default = false)
    {
        if (!is_array($attr)) {
            return (bool) $default;
        }

        foreach ($attr as $key => $value) {
            if (is_string($key) && strtolower($key) === $name) {
                if (is_bool($value)) {
                    return $value;
                }

                return 'false' !== strtolower(trim((string) $value));
            }

            if (is_int($key) && is_string($value) && strtolower($value) === $name) {
                return true;
            }
        }

        return (bool) $default;
    }

    /**
     * Make attributes safe and typed.
     *
     * @param array $atts
     * @return array
     */
    private function sanitize_atts(array $atts)
    {
        // 1) Merge with defaults so keys always exist
        //    (WP core helper keeps only known keys for this shortcode tag)
        $merged = shortcode_atts(
            $this->default_attr,
            $atts,
            Plugin::PLUGIN_SLUG.'_currency-converter'
        );

        // 2) Sanitize + types
        $title  = sanitize_text_field((string)$merged['title']);
        $amount = is_numeric($merged['amount']) ? (float)$merged['amount'] : (float)$this->default_attr['amount'];

        $from = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)$merged['from']));
        $to   = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)$merged['to']));
        if (!preg_match('/^[A-Z]{2,6}$/', $from)) { $from = $this->default_attr['from']; }
        if (!preg_match('/^[A-Z]{2,6}$/', $to))   { $to   = $this->default_attr['to']; }

        $code   = $this->parse_bool_attr($atts, 'code', $this->default_attr['code']);
        $border = $this->parse_bool_attr($atts, 'border', $this->default_attr['border']);
        $after  = $this->parse_bool_attr($atts, 'after', $this->default_attr['after']);
        $symbol = $this->parse_bool_attr($atts, 'symbol', $this->default_attr['symbol']);

        // ID: generate stable id if empty; sanitize provided
        $id = $merged['id'];
        if ($id === '' || $id === null) {
            // Stable but unique-ish per config (no time() to avoid cache-busting)
            $id = substr('w-'.md5($from.'|'.$to.'|'.$amount.'|'.(int)$code.(int)$border.(int)$after.(int)$symbol), 0, 12);
        } else {
            // allows [A-Za-z0-9_-] and keeps it CSS-safe
            $id = sanitize_html_class((string)$id);
        }

        return [
            'title'  => $title,
            'amount' => $amount,
            'from'   => $from,
            'to'     => $to,
            'code'   => $code,
            'border' => $border,
            'after'  => $after,
            'symbol' => $symbol,
            'id'     => $id,
        ];
    }

    public function shortcode($attr = [])
    {
        // Keep only allowed keys (defense-in-depth)
        if (is_array($attr)) {
            $attr = Tools::normalize_shortcode_boolean_flags($attr, ['code', 'border', 'after', 'symbol']);
            $attr = Tools::filter_keys_allowed_list(
                $attr,
                ['title','amount','from','to','code','border','after','symbol','id']
            );
        } else {
            $attr = [];
        }

        // Merge + sanitize
        $safe = $this->sanitize_atts($attr);

        // --- Ensure unique DOM id while keeping cache-friendly base id ---
        // comment: userProvidedId — true if user set `id` explicitly
        $userProvidedId = isset($attr['id']) && $attr['id'] !== '' && $attr['id'] !== null;

        // comment: baseId is stable (hash of params) or sanitized user id
        $baseId = $safe['id'];
        $domId  = $baseId;

        // comment: only auto-generated ids may be suffixed; user ids are respected as-is
        static $used = []; // request-scoped counter
        if (!$userProvidedId) {
            if (!isset($used[$baseId])) {
                $used[$baseId] = 1;               // first occurrence: keep base id
            } else {
                $used[$baseId]++;                  // next ones: -2, -3, ...
                $domId = $baseId . '-' . $used[$baseId];
            }
        }

        // Prepare service
        $obj = new Service_CurrencyConverter();
        $obj->parameters = array_merge($this->default_attr, $safe, ['id' => $domId]);

        // Render
        return '<div class="cr-'.esc_attr(Plugin::PLUGIN_SLUG).'">'.
            $obj->get_html_widget($domId).
        '</div>';
    }

}
