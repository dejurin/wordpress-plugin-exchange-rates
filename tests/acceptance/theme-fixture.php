<?php
/** Plugin Name: Isolated Exchange Rates theme lab */
if (empty($_GET['page_id'])) { return; }
if (!defined('ABSPATH') || '1' !== getenv('EXCHANGE_RATES_TEST_MODE') || 'local' !== wp_get_environment_type()) { return; }
require_once '/opt/tests/acceptance/asset-revision.php';
if (isset($_GET['er_locale']) && in_array($_GET['er_locale'], array('en_US', 'uk', 'bg_BG'), true)) {
    $er_lab_locale = $_GET['er_locale'];
    add_filter('locale', function () use ($er_lab_locale) { return $er_lab_locale; });
}
function er_lab_candidate() { return true; }
function er_lab_source() {
    $catalog = array('sources' => \Dejurin\ExchangeRates\Models\Sources::get_list());
    return isset($_GET['source'], $catalog['sources'][$_GET['source']]) ? $_GET['source'] : 'eu-ecb';
}
$er_lab_themes = array('twentytwentyfive','astra','kadence','generatepress','blocksy','hello-elementor');
if (isset($_GET['theme']) && in_array($_GET['theme'], $er_lab_themes, true)) {
    $er_lab_theme = $_GET['theme'];
    add_filter('pre_option_template', function () use ($er_lab_theme) { return $er_lab_theme; });
    add_filter('pre_option_stylesheet', function () use ($er_lab_theme) { return $er_lab_theme; });
}
add_filter('pre_http_request', function () { return new WP_Error('lab_network_disabled', 'Outbound HTTP is disabled in the isolated theme lab.'); }, 1);
add_filter('pre_option_blog_public', '__return_zero');
add_action('init', function () {
    if (isset($_GET['direction']) && 'rtl' === $_GET['direction']) { $GLOBALS['wp_locale']->text_direction = 'rtl'; wp_styles()->text_direction = 'rtl'; }
    add_filter('pre_option_exchange-rates', function () {
        return array_merge(\Dejurin\ExchangeRates\Models\Settings::get_defaults(), array('source_id'=>er_lab_source(), 'base_currency'=>'USD', 'currency_format'=>3, 'decimals'=>2, 'rates_available'=>true, 'update_timestamp'=>time()));
    });
    add_filter('pre_option_exchange-rates_rates', function () {
        $rates = array('USD'=>1,'EUR'=>0.92,'GBP'=>0.77,'JPY'=>157.69,'BRL'=>5.67,'AUD'=>1.51);
        $previous = $rates;
        // Synthetic trend fixtures are confined to this disposable lab.
        if (isset($_GET['trends']) && 'mixed' === $_GET['trends']) {
            $previous['EUR'] = 0.90;
            $previous['GBP'] = 0.80;
            unset($previous['JPY']);
        }
        if ('currencyrate' !== er_lab_source()) {
            foreach ($rates as &$rate) { $rate = 1 / $rate; } unset($rate);
            foreach ($previous as &$rate) { $rate = 1 / $rate; } unset($rate);
        }
        return array('source'=>er_lab_source(),'base'=>'USD','data'=>array(
            array('local_time'=>'2026-10-27 11:50:00','put_time'=>'2026-10-27 11:51:00','rates'=>$rates),
            array('local_time'=>'2026-10-26 11:50:00','put_time'=>'2026-10-26 11:51:00','rates'=>$previous)
        ));
    });
}, 0);
add_shortcode('er_theme_lab', function () {
    $surface = isset($_GET['surface']) && in_array($_GET['surface'],array('table','converter','badge'),true) ? $_GET['surface'] : 'table';
    $native = isset($_GET['native']);
    $count = isset($_GET['count']) && in_array((int)$_GET['count'],array(1,3,5),true) ? (int)$_GET['count'] : 3;
    $currencies = implode(',',array_slice(array('EUR','GBP','JPY','BRL','AUD'),0,$count));
    $base = isset($_GET['base']) && in_array($_GET['base'], array('USD','EUR','GBP'), true) ? $_GET['base'] : 'USD';
    $amount = isset($_GET['amount']) && in_array($_GET['amount'], array('0','1','2'), true) ? $_GET['amount'] : '1';
    $inverse = isset($_GET['inverse']) ? 'true' : 'false';
    $code = isset($_GET['names']) ? 'false' : 'true';
    $editable = isset($_GET['static']) ? 'false' : 'true';
    $history = isset($_GET['history']) ? 'true' : 'false';

    $html = '<div class="er-lab-status">Synthetic rates · '.esc_html(wp_get_theme()->get('Name')).' '.esc_html(wp_get_theme()->get('Version')).' · '.'Installed package '.'</div>';
    $html .= '<nav class="er-lab-nav" aria-label="Widget">';
    foreach (array('table'=>'Exchange Rates','converter'=>'Currency Converter','badge'=>'Currency Badge') as $key=>$label) { $html .= '<a href="'.esc_url(add_query_arg('surface',$key)).'">'.esc_html($label).'</a> '; }
    $html .= '</nav>';
    $html .= '<form class="er-lab-controls" method="get"><input type="hidden" name="surface" value="'.esc_attr($surface).'">';
    // Page 6 contains the real Elementor fixture; keep it selected for that theme.
    $html .= '<input type="hidden" name="page_id" value="'.get_the_ID().'">';
    $html .= '<label>Theme<select name="theme">';
    foreach (array('twentytwentyfive','astra','kadence','generatepress','blocksy','hello-elementor') as $slug) {
        $html .= '<option value="'.esc_attr($slug).'"'.selected(get_stylesheet(),$slug,false).'>'.esc_html(wp_get_theme($slug)->get('Name')).'</option>';
    }
    $html .= '</select></label><label>Source<select name="source">';
    $catalog = array('sources' => \Dejurin\ExchangeRates\Models\Sources::get_list());
    foreach ($catalog['sources'] as $id=>$item) { $html .= '<option value="'.esc_attr($id).'"'.selected(er_lab_source(),$id,false).'>'.esc_html((isset($item['compact']) ? $item['compact'] : $item['short']).' — '.$item['name']).'</option>'; }
    $html .= '</select></label><label>Badge currencies<select name="count">';
    foreach (array(1,3,5) as $n) { $html .= '<option value="'.$n.'"'.selected($count,$n,false).'>'.$n.'</option>'; }
    $html .= '</select></label><button type="submit">Apply preview</button></form><section id="lab-fixture"'.(isset($_GET['narrow']) ? ' style="max-width:260px"' : '').' data-surface="'.esc_attr($surface).'">';
    if ('table' === $surface && $native) {
        ob_start(); the_widget('\\Dejurin\ExchangeRates\Widgets\Table',array('title'=>'','base_currency'=>'USD','currency_list'=>array('EUR','GBP','JPY'),'code'=>true,'flag_type'=>'none','amount_active'=>true)); $html .= ob_get_clean();
    } elseif ('converter' === $surface && $native) {
        ob_start(); the_widget('\\Dejurin\ExchangeRates\Widgets\Converter',array('title'=>'Currency Converter','from'=>'USD','to'=>'EUR','amount'=>1)); $html .= ob_get_clean();
    } elseif ('table' === $surface) {
        $html .= do_shortcode('[exchange-rates_currency-table id="lab-table" base_currency="'.$base.'" amount="'.$amount.'" inverse="'.$inverse.'" currency_list="EUR,GBP,JPY" code="'.$code.'" flag_type="none" border="false" amount_active="'.$editable.'" table_headers_previous_close_show="'.$history.'" table_headers_changes_show="'.$history.'"]');
    } elseif ('converter' === $surface) {
        $html .= do_shortcode('[exchange-rates_currency-converter id="lab-converter" from="'.$base.'" to="'.('EUR' === $base ? 'GBP' : 'EUR').'" amount="'.$amount.'"]');
    } else {
        $html .= do_shortcode('[exchange-rates_badge id="lab-badge" base_currency="'.$base.'" amount="'.$amount.'" inverse="'.$inverse.'" currency_list="'.$currencies.'" code="'.$code.'" flag_type="none" base_show="false"]');
    }
    if (isset($_GET['multi'])) {
        $html .= do_shortcode('[exchange-rates_currency-converter id="lab-extra-converter" from="USD" to="EUR"] [exchange-rates_badge id="lab-extra-badge" currency_list="EUR,GBP,JPY" code="true" flag_type="none"]');
    }
    return $html.'</section>';
});

// Deliberately remove scripts only for the lab's no-JavaScript CSS check.
add_action('template_redirect', function () {
    if (isset($_GET['nojs'])) {
        ob_start(function ($html) { return preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html); });
    }
});


add_action('wp_head', function () { echo '<style>#lab-fixture{max-width:640px}.er-lab-nav{display:flex;gap:12px;font-size:13px}.er-lab-status,.er-lab-controls{font-size:12px}.er-lab-controls{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0 24px}.er-lab-controls label{display:flex;flex-direction:column}.er-lab-controls select{max-width:240px}</style>'; });
