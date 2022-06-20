<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Sources
{
    public static function get_list()
    {
        return [
            'currencyrate' => ['name' => __('CurrencyRate.Today', Plugin::PLUGIN_SLUG), 'country' => 'xx', 'source_url' => 'https://currencyrate.today/', 'short' => 'CurrencyRate'],
            'ba-cbbh' => ['name' => __('The Central Bank of Bosnia and Herzegovina', Plugin::PLUGIN_SLUG), 'country' => 'ba', 'source_url' => 'https://www.cbbh.ba/', 'short' => 'BH Bank'],
            'eg-cbe' => ['name' => __('Central Bank of Egypt', Plugin::PLUGIN_SLUG), 'country' => 'eg', 'source_url' => 'https://www.cbe.org.eg/', 'short' => 'CBE'],
            'bd-bb' => ['name' => __('Bangladesh Bank', Plugin::PLUGIN_SLUG), 'country' => 'bd', 'source_url' => 'https://bb.org.bd/', 'short' => 'Bangladesh Bank'],
            'bh-cbb' => ['name' => __('Central Bank of Bahrain', Plugin::PLUGIN_SLUG), 'country' => 'bh', 'source_url' => 'https://www.cbb.gov.bh/', 'short' => 'Bahrain Bank'],
            'ir-cbi' => ['name' => __('Central Bank of the Islamic Republic of Iran', Plugin::PLUGIN_SLUG), 'country' => 'ir', 'source_url' => 'https://www.cbi.ir/', 'short' => 'CBI Iran'],
            'md-bnm' => ['name' => __('National Bank of Moldova', Plugin::PLUGIN_SLUG), 'country' => 'md', 'source_url' => 'https://www.bnm.md/', 'short' => 'BNM'],
            'tm-cbt' => ['name' => __('Central Bank of Turkmenistan', Plugin::PLUGIN_SLUG), 'country' => 'tm', 'source_url' => 'https://www.cbt.tm/', 'short' => 'CBT'],
            'mx-banxico' => ['name' => __('Bank of Mexico', Plugin::PLUGIN_SLUG), 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/', 'short' => 'Banxico'],
            'ar-bcra-uva' => ['name' => __('Central Bank of Argentina (uva)', Plugin::PLUGIN_SLUG), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'BCRA (uva)'],
            'cl-bc' => ['name' => __('Central Bank of Chile', Plugin::PLUGIN_SLUG), 'country' => 'cl', 'source_url' => 'https://www.bcentral.cl/', 'short' => 'Chile Bank'],
            'ar-bcra-wholesale' => ['name' => __('Central Bank of Argentina (wholesale)', Plugin::PLUGIN_SLUG), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'BCRA (wholesale)'],
            'ar-bcra-retail' => ['name' => __('Central Bank of Argentina (retail)', Plugin::PLUGIN_SLUG), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'BCRA (retail)'],
            'ca-boc' => ['name' => __('Bank of Canada', Plugin::PLUGIN_SLUG), 'country' => 'ca', 'source_url' => 'https://www.bankofcanada.ca/', 'short' => 'Canada Bank'],
            've-bcv' => ['name' => __('Central bank of Venezuela', Plugin::PLUGIN_SLUG), 'country' => 've', 'source_url' => 'http://www.bcv.org.ve/', 'short' => 'BCV'],
            'cu-bc' => ['name' => __('Central Bank of Cuba', Plugin::PLUGIN_SLUG), 'country' => 'cu', 'source_url' => 'https://www.bc.gob.cu/', 'short' => 'Cuba Bank'],
            'np-nrb' => ['name' => __('Nepal Rastra bank', Plugin::PLUGIN_SLUG), 'country' => 'np', 'source_url' => 'https://www.nrb.org.np/', 'short' => 'Nepal Rastra'],
            'mx-banxico-fix' => ['name' => __('Bank of Mexico (FIX)', Plugin::PLUGIN_SLUG), 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/tipcamb/tipCamMIAction.do', 'short' => 'Banxico (fix)'],
            'is-cb' => ['name' => __('Central Bank of Iceland', Plugin::PLUGIN_SLUG), 'country' => 'is', 'source_url' => 'https://www.cb.is/', 'short' => 'Iceland Bank'],
            'tn-bct' => ['name' => __('Central Bank of Tunisia', Plugin::PLUGIN_SLUG), 'country' => 'tn', 'source_url' => 'https://www.bct.gov.tn', 'short' => 'Tunisia Bank'],
            'pmr-cb' => ['name' => __('Transnistrian Republican Bank', Plugin::PLUGIN_SLUG), 'country' => 'xx', 'source_url' => 'https://cbpmr.net/', 'short' => 'CB PMR'],
            'bg-bnb' => ['name' => __('Bulgarian National Bank', Plugin::PLUGIN_SLUG), 'country' => 'bg', 'source_url' => 'https://www.bnb.bg/', 'short' => 'BNB'],
            'eu-ecb' => ['name' => __('European Central Bank', Plugin::PLUGIN_SLUG), 'country' => 'eu', 'source_url' => 'https://www.ecb.europa.eu/', 'short' => 'ECB'],
            'ae-cbae' => ['name' => __('Central Bank of the UAE', Plugin::PLUGIN_SLUG), 'country' => 'ae', 'source_url' => 'https://centralbank.ae/', 'short' => 'UAE Bank'],
            'kz-nbkz' => ['name' => __('National Bank of Kazakhstan', Plugin::PLUGIN_SLUG), 'country' => 'kz', 'source_url' => 'https://www.nationalbank.kz/', 'short' => 'Kazakhstan'],
            'dk-dnb' => ['name' => __('Danmarks Nationalbank', Plugin::PLUGIN_SLUG), 'country' => 'dk', 'source_url' => 'https://www.nationalbanken.dk/', 'short' => 'Danmarks'],
            'ru-cbr' => ['name' => __('The Central Bank of the Russian Federation', Plugin::PLUGIN_SLUG), 'country' => 'ru', 'source_url' => 'https://www.cbr.ru/', 'short' => 'CBR'],
            'tj-nbt' => ['name' => __('National Bank of Tajikistan', Plugin::PLUGIN_SLUG), 'country' => 'tj', 'source_url' => 'https://www.nbt.tj/', 'short' => 'NBT'],
            'ge-nbg' => ['name' => __('The National Bank of Georgia', Plugin::PLUGIN_SLUG), 'country' => 'ge', 'source_url' => 'https://nbg.gov.ge/', 'short' => 'NBG GE'],
            'ua-nbu' => ['name' => __('National Bank of Ukraine', Plugin::PLUGIN_SLUG), 'country' => 'ua', 'source_url' => 'https://bank.gov.ua/', 'short' => 'NBU'],
            'tr-tcmb' => ['name' => __('Central Bank of the Republic of Turkey', Plugin::PLUGIN_SLUG), 'country' => 'tr', 'source_url' => 'https://www.tcmb.gov.tr/', 'short' => 'TCMB'],
            'cz-cnb' => ['name' => __('Czech National Bank', Plugin::PLUGIN_SLUG), 'country' => 'cz', 'source_url' => 'https://www.bc.gob.cu/', 'short' => 'Czech Bank'],
            'ma-bkam' => ['name' => __('BANK AL-MAGHRIB', Plugin::PLUGIN_SLUG), 'country' => 'ma', 'source_url' => 'https://www.bkam.ma/', 'short' => 'BKAM'],
            'hr-hnb' => ['name' => __('Croatian National Bank', Plugin::PLUGIN_SLUG), 'country' => 'hr', 'source_url' => 'https://www.hnb.hr/', 'short' => 'HNB HR'],
            'th-bot' => ['name' => __('Bank of Thailand', Plugin::PLUGIN_SLUG), 'country' => 'th', 'source_url' => 'https://www.bot.or.th/', 'short' => 'Thai Bank'],
            'se-riksbank' => ['name' => __('Riksbank', Plugin::PLUGIN_SLUG), 'country' => 'se', 'source_url' => 'https://www.riksbank.se/', 'short' => 'Riksbank'],
            'ro-bnr' => ['name' => __('National Bank of Romania', Plugin::PLUGIN_SLUG), 'country' => 'ro', 'source_url' => 'https://www.bnr.ro/', 'short' => 'BNR'],
            'pl-nbp' => ['name' => __('Polish National Bank', Plugin::PLUGIN_SLUG), 'country' => 'pl', 'source_url' => 'https://www.nbp.pl/', 'short' => 'NBP'],
            'il-boi' => ['name' => __('Bank of Israel', Plugin::PLUGIN_SLUG), 'country' => 'il', 'source_url' => 'https://www.boi.org.il/', 'short' => 'Israel Bank'],
            'hu-mnb' => ['name' => __('Hungarian National Bank', Plugin::PLUGIN_SLUG), 'country' => 'hu', 'source_url' => 'https://www.mnb.hu/', 'short' => 'MNB'],
            'ch-snb' => ['name' => __('Swiss National Bank', Plugin::PLUGIN_SLUG), 'country' => 'ch', 'source_url' => 'https://www.snb.ch/', 'short' => 'Swiss Bank'],
            'tw-cbc' => ['name' => __('Central Bank of the Republic of China', Plugin::PLUGIN_SLUG), 'country' => 'tw', 'source_url' => 'https://www.cbc.gov.tw/en/mp-2.html', 'short' => 'CBC TW'],
            'ph-bsp' => ['name' => __('Central Bank of the Philippines', Plugin::PLUGIN_SLUG), 'country' => 'ph', 'source_url' => 'https://www.bsp.gov.ph/', 'short' => 'BSP PH'],
            'in-fbil' => ['name' => __('Financial Benchmarks India', Plugin::PLUGIN_SLUG), 'country' => 'in', 'source_url' => 'https://www.fbil.org.in/', 'short' => 'FBIL'],
            'gb-boe' => ['name' => __('Bank of England', Plugin::PLUGIN_SLUG), 'country' => 'gb', 'source_url' => 'https://www.bankofengland.co.uk/', 'short' => 'England Bank'],
            'et-nbe' => ['name' => __('National Bank of Ethiopia', Plugin::PLUGIN_SLUG), 'country' => 'et', 'source_url' => 'https://nbe.gov.et/', 'short' => 'Ethiopia Bank'],
            'bt-rma' => ['name' => __('Royal Monetary Authority of Bhutan', Plugin::PLUGIN_SLUG), 'country' => 'bt', 'source_url' => 'http://www.rma.org.bt/', 'short' => 'Bhutan Monetary'],
            'az-cbar' => ['name' => __('Central Bank of the Republic of Azerbaijan', Plugin::PLUGIN_SLUG), 'country' => 'az', 'source_url' => 'https://www.cbar.az/', 'short' => 'CBAR AZ'],
            'au-rba' => ['name' => __('Reserve Bank of Australia', Plugin::PLUGIN_SLUG), 'country' => 'au', 'source_url' => 'https://www.rba.gov.au/', 'short' => 'RBA AU'],
            'uz-cbu' => ['name' => __('The Central Bank of the Republic of Uzbekistan', Plugin::PLUGIN_SLUG), 'country' => 'uz', 'source_url' => 'https://cbu.uz/', 'short' => 'CBU UZ'],
            'ng-cbn' => ['name' => __('Central Bank of Nigeria', Plugin::PLUGIN_SLUG), 'country' => 'ng', 'source_url' => 'https://www.cbn.gov.ng/', 'short' => 'Nigeria Bank'],
            'kh-nbc' => ['name' => __('Bank of Cambodia', Plugin::PLUGIN_SLUG), 'country' => 'kh', 'source_url' => 'https://www.nbc.org.kh/', 'short' => 'Cambodia Bank'],
            'sg-mas' => ['name' => __('Monetary Authority of Singapore', Plugin::PLUGIN_SLUG), 'country' => 'sg', 'source_url' => 'https://www.mas.gov.sg/', 'short' => 'MAS SG'],
        ];
    }
}
