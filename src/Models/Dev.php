<?php

namespace Dejurin\ExchangeRates\Models;

// use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Plugin;

class Dev
{
    public static function caption($parameters, $time, $caption_id, $source_id)
    {
        // $get_currencies = Currencies::get_currencies();

        $put_time = new \DateTime($time['put_time']);
        $local_time = new \DateTime($time['local_time']);
        $_ate = 'Rate';
        $_urrency = 'Currency';

        $get_sources = Sources::get_list();
        $source = $get_sources[$source_id];

        $base_currency = isset($parameters['base_currency']) && !empty($parameters['base_currency']) ? strtoupper($parameters['base_currency']) : 'USD';
        $quote_currency = isset($parameters['quote_currency']) && !empty($parameters['quote_currency']) ? strtoupper($parameters['quote_currency']) : null;
        
        $_base_currency = 'USD' === $base_currency && is_null($quote_currency) ? 'usd' : strtolower($base_currency);
        $_quote_currency = !is_null($quote_currency) && $base_currency !== $quote_currency ? strtolower($quote_currency) : '';



        $html = '<div class="d-flex exchange-rates-sign">';

        $template = '<a href="'
                    .((!is_null($quote_currency)) ? 'https://'.$_base_currency.'.currencyrate.today/'.$_quote_currency : 'https://'.$_base_currency.'.currencyrate.today').'"'
                    .'target="_blank" '
                    .'rel="noreferrer noopener">'
                    .'%2$s</a>&nbsp;&middot;&nbsp;%3$s&nbsp;';

        $html .= sprintf(
            $template,
            $_urrency.$_ate,
            $parameters['base_currency'].(!is_null($quote_currency) ? '/'.$quote_currency : ''),
            $local_time->format('d M')
        );

        $html .= '<span title="'.__('Info', Plugin::PLUGIN_SLUG).'" data-caption-id="'.Plugin::PLUGIN_SLUG.'-info-caption-'.$caption_id.'" class="d-flex info '.Plugin::PLUGIN_SLUG.'-caption-button">'
              .'<svg xmlns="http://www.w3.org/2000/svg" height="20" width="20">'
              .'<path d="M9.125 14.167H10.875V9.167H9.125ZM10 7.479Q10.354 7.479 10.615 7.219Q10.875 6.958 10.875 6.604Q10.875 6.25 10.615 5.99Q10.354 5.729 10 5.729Q9.646 5.729 9.385 5.99Q9.125 6.25 9.125 6.604Q9.125 6.958 9.385 7.219Q9.646 7.479 10 7.479ZM10 18.333Q8.271 18.333 6.75 17.677Q5.229 17.021 4.104 15.896Q2.979 14.771 2.323 13.25Q1.667 11.729 1.667 10Q1.667 8.271 2.323 6.75Q2.979 5.229 4.104 4.104Q5.229 2.979 6.75 2.323Q8.271 1.667 10 1.667Q11.729 1.667 13.25 2.323Q14.771 2.979 15.896 4.104Q17.021 5.229 17.677 6.75Q18.333 8.271 18.333 10Q18.333 11.729 17.677 13.25Q17.021 14.771 15.896 15.896Q14.771 17.021 13.25 17.677Q11.729 18.333 10 18.333ZM10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10ZM10 16.583Q12.729 16.583 14.656 14.656Q16.583 12.729 16.583 10Q16.583 7.271 14.656 5.344Q12.729 3.417 10 3.417Q7.271 3.417 5.344 5.344Q3.417 7.271 3.417 10Q3.417 12.729 5.344 14.656Q7.271 16.583 10 16.583Z"/></svg>'
              .'<svg style="display:none;" xmlns="http://www.w3.org/2000/svg" height="20" width="20"><path d="M7.021 14.188 10 11.208 12.979 14.188 14.208 12.958 11.229 10 14.208 7.021 12.979 5.792 10 8.771 7.021 5.792 5.812 7 8.792 10 5.812 12.979ZM10 18.333Q8.271 18.333 6.75 17.677Q5.229 17.021 4.104 15.896Q2.979 14.771 2.323 13.25Q1.667 11.729 1.667 10Q1.667 8.271 2.323 6.75Q2.979 5.229 4.104 4.104Q5.229 2.979 6.75 2.323Q8.271 1.667 10 1.667Q11.729 1.667 13.25 2.323Q14.771 2.979 15.896 4.104Q17.021 5.229 17.677 6.75Q18.333 8.271 18.333 10Q18.333 11.729 17.677 13.25Q17.021 14.771 15.896 15.896Q14.771 17.021 13.25 17.677Q11.729 18.333 10 18.333ZM10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10Q10 10 10 10ZM10 16.583Q12.729 16.583 14.656 14.656Q16.583 12.729 16.583 10Q16.583 7.271 14.656 5.344Q12.729 3.417 10 3.417Q7.271 3.417 5.344 5.344Q3.417 7.271 3.417 10Q3.417 12.729 5.344 14.656Q7.271 16.583 10 16.583Z"/></svg></span></div>';

        $html .= '<div id="'.Plugin::PLUGIN_SLUG.'-info-caption-'.$caption_id.'" class="'.Plugin::PLUGIN_SLUG.'-info-caption">'
                .'<div>'
                .'<b><a href="'.$source['source_url'].'" target="_blank" rel="noreferrer noopener nofollow">'.$source['name'].'</a></b><br>'
                .'<b>'.__('Check:', Plugin::PLUGIN_SLUG).'</b> '.$put_time->format('d M Y H:i').' UTC<br>'
                .'<b>'.__('Latest change:', Plugin::PLUGIN_SLUG).'</b> '.$local_time->format('d M Y H:i').' UTC'
                .'</div>'
                .'<div><small><b>'.__('Disclaimers.', Plugin::PLUGIN_SLUG).'</b> '.__('This plugin or website cannot guarantee the accuracy of the exchange rates displayed. You should confirm current rates before making any transactions that could be affected by changes in the exchange rates.', Plugin::PLUGIN_SLUG).'</small></div>'
                .'</div>';

        return $html;
    }
}
