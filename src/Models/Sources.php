<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Sources
{
    public static function get_sources()
    {
        return [
            'currencyrate' => ['name' => __('CurrencyRate.Today', Plugin::PLUGIN_SLUG), 'country' => 'xx', 'source_url' => 'https://currencyrate.today/'],
            'ba-cbbh' => ['name' => __('The Central Bank of Bosnia and Herzegovina', Plugin::PLUGIN_SLUG), 'country' => 'ba', 'source_url' => 'https://www.cbbh.ba/'],
            'eg-cbe' => ['name' => __('Central Bank of Egypt', Plugin::PLUGIN_SLUG), 'country' => 'eg', 'source_url' => 'https://www.cbe.org.eg/'],
            'bd-bb' => ['name' => __('Bangladesh Bank', Plugin::PLUGIN_SLUG), 'country' => 'bd', 'source_url' => 'https://bb.org.bd/'],
            'bh-cbb' => ['name' => __('Central Bank of Bahrain', Plugin::PLUGIN_SLUG), 'country' => 'bh', 'source_url' => 'https://www.cbb.gov.bh/'],
            'ir-cbi' => ['name' => __('Central Bank of the Islamic Republic of Iran', Plugin::PLUGIN_SLUG), 'country' => 'ir', 'source_url' => 'https://www.fbil.org.in/'],
            'md-bnm' => ['name' => __('National Bank of Moldova', Plugin::PLUGIN_SLUG), 'country' => 'md', 'source_url' => 'https://www.bnm.md/'],
            'tm-cbt' => ['name' => __('Central Bank of Turkmenistan', Plugin::PLUGIN_SLUG), 'country' => 'tm', 'source_url' => 'https://www.cbt.tm/'],
            'mx-banxico' => ['name' => __('Bank of Mexico', Plugin::PLUGIN_SLUG), 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/'],
            'ar-bcra-uva' => ['name' => __('Central Bank of Argentina (uva)', Plugin::PLUGIN_SLUG), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/'],
            'cl-bc' => ['name' => __('Central Bank of Chile', Plugin::PLUGIN_SLUG), 'country' => 'cl', 'source_url' => 'https://www.bcentral.cl/'],
            'ar-bcra-wholesale' => ['name' => __('Central Bank of Argentina (wholesale)', Plugin::PLUGIN_SLUG), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/'],
            'ar-bcra-retail' => ['name' => __('Central Bank of Argentina (retail)', Plugin::PLUGIN_SLUG), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/'],
            'ca-boc' => ['name' => __('Bank of Canada', Plugin::PLUGIN_SLUG), 'country' => 'ca', 'source_url' => 'https://www.bankofcanada.ca/'],
            've-bcv' => ['name' => __('Central bank of Venezuela', Plugin::PLUGIN_SLUG), 'country' => 've', 'source_url' => 'http://www.bcv.org.ve/'],
            'cu-bc' => ['name' => __('Central Bank of Cuba', Plugin::PLUGIN_SLUG), 'country' => 'cu', 'source_url' => 'https://www.bc.gob.cu/'],
            'np-nrb' => ['name' => __('Nepal Rastra bank', Plugin::PLUGIN_SLUG), 'country' => 'np', 'source_url' => 'https://www.nrb.org.np/'],
            'mx-banxico-fix' => ['name' => __('Bank of Mexico (FIX)', Plugin::PLUGIN_SLUG), 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/tipcamb/tipCamMIAction.do'],
            'is-cb' => ['name' => __('Central Bank of Iceland', Plugin::PLUGIN_SLUG), 'country' => 'is', 'source_url' => 'https://www.cb.is/'],
            'tn-bct' => ['name' => __('Central Bank of Tunisia', Plugin::PLUGIN_SLUG), 'country' => 'tn', 'source_url' => 'https://www.bct.gov.tn'],
            'pmr-cb' => ['name' => __('Transnistrian Republican Bank', Plugin::PLUGIN_SLUG), 'country' => 'xx', 'source_url' => 'https://cbpmr.net/'],
            'bg-bnb' => ['name' => __('Bulgarian National Bank', Plugin::PLUGIN_SLUG), 'country' => 'bg', 'source_url' => 'https://www.bnb.bg/'],
            'eu-ecb' => ['name' => __('European Central Bank', Plugin::PLUGIN_SLUG), 'country' => 'eu', 'source_url' => 'https://www.ecb.europa.eu/'],
            'ae-cbae' => ['name' => __('Central Bank of the UAE', Plugin::PLUGIN_SLUG), 'country' => 'ae', 'source_url' => 'https://centralbank.ae/'],
            'kz-nbkz' => ['name' => __('National Bank of Kazakhstan', Plugin::PLUGIN_SLUG), 'country' => 'kz', 'source_url' => 'https://www.nationalbank.kz/'],
            'dk-dnb' => ['name' => __('Danmarks Nationalbank', Plugin::PLUGIN_SLUG), 'country' => 'dk', 'source_url' => 'https://www.nationalbanken.dk/'],
            'ru-cbr' => ['name' => __('The Central Bank of the Russian Federation', Plugin::PLUGIN_SLUG), 'country' => 'ru', 'source_url' => 'https://www.mas.gov.sg/'],
            'tj-nbt' => ['name' => __('National Bank of Tajikistan', Plugin::PLUGIN_SLUG), 'country' => 'tj', 'source_url' => 'https://www.nbt.tj/'],
            'ge-nbg' => ['name' => __('The National Bank of Georgia', Plugin::PLUGIN_SLUG), 'country' => 'ge', 'source_url' => 'https://nbg.gov.ge/'],
            'ua-nbu' => ['name' => __('National Bank of Ukraine', Plugin::PLUGIN_SLUG), 'country' => 'ua', 'source_url' => 'https://bank.gov.ua/'],
            'tr-tcmb' => ['name' => __('Central Bank of the Republic of Turkey', Plugin::PLUGIN_SLUG), 'country' => 'tr', 'source_url' => 'https://www.tcmb.gov.tr/'],
            'cz-cnb' => ['name' => __('Czech National Bank', Plugin::PLUGIN_SLUG), 'country' => 'cz', 'source_url' => 'https://www.bc.gob.cu/'],
            'ma-bkam' => ['name' => __('BANK AL-MAGHRIB', Plugin::PLUGIN_SLUG), 'country' => 'ma', 'source_url' => 'https://www.bkam.ma/'],
            'hr-hnb' => ['name' => __('Croatian National Bank', Plugin::PLUGIN_SLUG), 'country' => 'hr', 'source_url' => 'https://www.bankofengland.co.uk/'],
            'th-bot' => ['name' => __('Bank of Thailand', Plugin::PLUGIN_SLUG), 'country' => 'th', 'source_url' => 'https://www.bot.or.th/'],
            'se-riksbank' => ['name' => __('Riksbank', Plugin::PLUGIN_SLUG), 'country' => 'se', 'source_url' => 'https://www.riksbank.se/'],
            'ro-bnr' => ['name' => __('National Bank of Romania', Plugin::PLUGIN_SLUG), 'country' => 'ro', 'source_url' => 'https://www.bnr.ro/'],
            'pl-nbp' => ['name' => __('Polish National Bank', Plugin::PLUGIN_SLUG), 'country' => 'pl', 'source_url' => 'https://www.nbp.pl/'],
            'il-boi' => ['name' => __('Bank of Israel', Plugin::PLUGIN_SLUG), 'country' => 'il', 'source_url' => 'https://www.boi.org.il/'],
            'hu-mnb' => ['name' => __('Hungarian National Bank', Plugin::PLUGIN_SLUG), 'country' => 'hu', 'source_url' => 'https://www.mnb.hu/'],
            'ch-snb' => ['name' => __('Swiss National Bank', Plugin::PLUGIN_SLUG), 'country' => 'ch', 'source_url' => 'https://www.snb.ch/'],
            'tw-cbc' => ['name' => __('Central Bank of the Republic of China', Plugin::PLUGIN_SLUG), 'country' => 'tw', 'source_url' => 'https://www.cbc.gov.tw/en/mp-2.html'],
            'ph-bsp' => ['name' => __('Central Bank of the Philippines', Plugin::PLUGIN_SLUG), 'country' => 'ph', 'source_url' => 'https://www.bsp.gov.ph/'],
            'in-fbil' => ['name' => __('Financial Benchmarks India', Plugin::PLUGIN_SLUG), 'country' => 'in', 'source_url' => 'https://www.fbil.org.in/'],
            'gb-boe' => ['name' => __('Bank of England', Plugin::PLUGIN_SLUG), 'country' => 'gb', 'source_url' => 'https://www.bankofengland.co.uk/'],
            'et-nbe' => ['name' => __('National Bank of Ethiopia', Plugin::PLUGIN_SLUG), 'country' => 'et', 'source_url' => 'https://nbe.gov.et/'],
            'bt-rma' => ['name' => __('Royal Monetary Authority of Bhutan', Plugin::PLUGIN_SLUG), 'country' => 'bt', 'source_url' => 'http://www.rma.org.bt/'],
            'az-cbar' => ['name' => __('Central Bank of the Republic of Azerbaijan', Plugin::PLUGIN_SLUG), 'country' => 'az', 'source_url' => 'https://www.rba.gov.au/'],
            'au-rba' => ['name' => __('Reserve Bank of Australia', Plugin::PLUGIN_SLUG), 'country' => 'au', 'source_url' => 'https://www.rba.gov.au/'],
            'uz-cbu' => ['name' => __('The Central Bank of the Republic of Uzbekistan', Plugin::PLUGIN_SLUG), 'country' => 'uz', 'source_url' => 'https://cbu.uz/'],
            'ng-cbn' => ['name' => __('Central Bank of Nigeria', Plugin::PLUGIN_SLUG), 'country' => 'ng', 'source_url' => 'https://www.cbn.gov.ng/'],
            'kh-nbc' => ['name' => __('Bank of Cambodia', Plugin::PLUGIN_SLUG), 'country' => 'kh', 'source_url' => 'https://www.nbc.org.kh/'],
            'sg-mas' => ['name' => __('Monetary Authority of Singapore', Plugin::PLUGIN_SLUG), 'country' => 'sg', 'source_url' => 'https://www.mas.gov.sg/'],
        ];
    }
}
