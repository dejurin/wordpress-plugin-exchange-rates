<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Sources
{
    public static function get_list()
    {
        return [
            'currencyrate' => ['name' => esc_html__('CurrencyRate.Today', 'exchange-rates'), 'country' => 'xx', 'source_url' => 'https://currencyrate.today/', 'short' => 'CurrencyRate'],
            'ba-cbbh' => ['name' => esc_html__('The Central Bank of Bosnia and Herzegovina', 'exchange-rates'), 'country' => 'ba', 'source_url' => 'https://www.cbbh.ba/', 'short' => 'BH Bank'],
            'eg-cbe' => ['name' => esc_html__('Central Bank of Egypt', 'exchange-rates'), 'country' => 'eg', 'source_url' => 'https://www.cbe.org.eg/', 'short' => 'CBE'],
            'bd-bb' => ['name' => esc_html__('Bangladesh Bank', 'exchange-rates'), 'country' => 'bd', 'source_url' => 'https://bb.org.bd/', 'short' => 'Bangladesh Bank'],
            'bh-cbb' => ['name' => esc_html__('Central Bank of Bahrain', 'exchange-rates'), 'country' => 'bh', 'source_url' => 'https://www.cbb.gov.bh/', 'short' => 'Bahrain Bank'],
            'ir-cbi' => ['name' => esc_html__('Central Bank of the Islamic Republic of Iran', 'exchange-rates'), 'country' => 'ir', 'source_url' => 'https://www.cbi.ir/', 'short' => 'CBI Iran'],
            'md-bnm' => ['name' => esc_html__('National Bank of Moldova', 'exchange-rates'), 'country' => 'md', 'source_url' => 'https://www.bnm.md/', 'short' => 'BNM'],
            'tm-cbt' => ['name' => esc_html__('Central Bank of Turkmenistan', 'exchange-rates'), 'country' => 'tm', 'source_url' => 'https://www.cbt.tm/', 'short' => 'CBT'],
            'mx-banxico' => ['name' => esc_html__('Bank of Mexico', 'exchange-rates'), 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/', 'short' => 'Banxico'],
            'ar-bcra-uva' => ['name' => esc_html__('Central Bank of Argentina (uva)', 'exchange-rates'), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'BCRA (uva)'],
            'cl-bc' => ['name' => esc_html__('Central Bank of Chile', 'exchange-rates'), 'country' => 'cl', 'source_url' => 'https://www.bcentral.cl/', 'short' => 'Chile Bank'],
            'ar-bcra-wholesale' => ['name' => esc_html__('Central Bank of Argentina (wholesale)', 'exchange-rates'), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'BCRA (wholesale)'],
            'ar-bcra-retail' => ['name' => esc_html__('Central Bank of Argentina (retail)', 'exchange-rates'), 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'BCRA (retail)'],
            'ca-boc' => ['name' => esc_html__('Bank of Canada', 'exchange-rates'), 'country' => 'ca', 'source_url' => 'https://www.bankofcanada.ca/', 'short' => 'Canada Bank'],
            've-bcv' => ['name' => esc_html__('Central bank of Venezuela', 'exchange-rates'), 'country' => 've', 'source_url' => 'http://www.bcv.org.ve/', 'short' => 'BCV'],
            'cu-bc' => ['name' => esc_html__('Central Bank of Cuba', 'exchange-rates'), 'country' => 'cu', 'source_url' => 'https://www.bc.gob.cu/', 'short' => 'Cuba Bank'],
            'np-nrb' => ['name' => esc_html__('Nepal Rastra bank', 'exchange-rates'), 'country' => 'np', 'source_url' => 'https://www.nrb.org.np/', 'short' => 'Nepal Rastra'],
            'mx-banxico-fix' => ['name' => esc_html__('Bank of Mexico (FIX)', 'exchange-rates'), 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/tipcamb/tipCamMIAction.do', 'short' => 'Banxico (fix)'],
            'is-cb' => ['name' => esc_html__('Central Bank of Iceland', 'exchange-rates'), 'country' => 'is', 'source_url' => 'https://www.cb.is/', 'short' => 'Iceland Bank'],
            'tn-bct' => ['name' => esc_html__('Central Bank of Tunisia', 'exchange-rates'), 'country' => 'tn', 'source_url' => 'https://www.bct.gov.tn', 'short' => 'Tunisia Bank'],
            'pmr-cb' => ['name' => esc_html__('Transnistrian Republican Bank', 'exchange-rates'), 'country' => 'xx', 'source_url' => 'https://cbpmr.net/', 'short' => 'CB PMR'],
            'bg-bnb' => ['name' => esc_html__('Bulgarian National Bank', 'exchange-rates'), 'country' => 'bg', 'source_url' => 'https://www.bnb.bg/', 'short' => 'BNB'],
            'eu-ecb' => ['name' => esc_html__('European Central Bank', 'exchange-rates'), 'country' => 'eu', 'source_url' => 'https://www.ecb.europa.eu/', 'short' => 'ECB'],
            'ae-cbae' => ['name' => esc_html__('Central Bank of the UAE', 'exchange-rates'), 'country' => 'ae', 'source_url' => 'https://centralbank.ae/', 'short' => 'UAE Bank'],
            'kz-nbkz' => ['name' => esc_html__('National Bank of Kazakhstan', 'exchange-rates'), 'country' => 'kz', 'source_url' => 'https://www.nationalbank.kz/', 'short' => 'Kazakhstan'],
            'dk-dnb' => ['name' => esc_html__('Danmarks Nationalbank', 'exchange-rates'), 'country' => 'dk', 'source_url' => 'https://www.nationalbanken.dk/', 'short' => 'Danmarks'],
            'ru-cbr' => ['name' => esc_html__('The Central Bank of the Russian Federation', 'exchange-rates'), 'country' => 'ru', 'source_url' => 'https://www.cbr.ru/', 'short' => 'CBR'],
            'tj-nbt' => ['name' => esc_html__('National Bank of Tajikistan', 'exchange-rates'), 'country' => 'tj', 'source_url' => 'https://www.nbt.tj/', 'short' => 'NBT'],
            'ge-nbg' => ['name' => esc_html__('The National Bank of Georgia', 'exchange-rates'), 'country' => 'ge', 'source_url' => 'https://nbg.gov.ge/', 'short' => 'NBG GE'],
            'ua-nbu' => ['name' => esc_html__('National Bank of Ukraine', 'exchange-rates'), 'country' => 'ua', 'source_url' => 'https://bank.gov.ua/', 'short' => 'NBU'],
            'tr-tcmb' => ['name' => esc_html__('Central Bank of the Republic of Turkey', 'exchange-rates'), 'country' => 'tr', 'source_url' => 'https://www.tcmb.gov.tr/', 'short' => 'TCMB'],
            'cz-cnb' => ['name' => esc_html__('Czech National Bank', 'exchange-rates'), 'country' => 'cz', 'source_url' => 'https://www.bc.gob.cu/', 'short' => 'Czech Bank'],
            'ma-bkam' => ['name' => esc_html__('BANK AL-MAGHRIB', 'exchange-rates'), 'country' => 'ma', 'source_url' => 'https://www.bkam.ma/', 'short' => 'BKAM'],
            'hr-hnb' => ['name' => esc_html__('Croatian National Bank', 'exchange-rates'), 'country' => 'hr', 'source_url' => 'https://www.hnb.hr/', 'short' => 'HNB HR'],
            'th-bot' => ['name' => esc_html__('Bank of Thailand', 'exchange-rates'), 'country' => 'th', 'source_url' => 'https://www.bot.or.th/', 'short' => 'Thai Bank'],
            'se-riksbank' => ['name' => esc_html__('Riksbank', 'exchange-rates'), 'country' => 'se', 'source_url' => 'https://www.riksbank.se/', 'short' => 'Riksbank'],
            'ro-bnr' => ['name' => esc_html__('National Bank of Romania', 'exchange-rates'), 'country' => 'ro', 'source_url' => 'https://www.bnr.ro/', 'short' => 'BNR'],
            'pl-nbp' => ['name' => esc_html__('Polish National Bank', 'exchange-rates'), 'country' => 'pl', 'source_url' => 'https://www.nbp.pl/', 'short' => 'NBP'],
            'il-boi' => ['name' => esc_html__('Bank of Israel', 'exchange-rates'), 'country' => 'il', 'source_url' => 'https://www.boi.org.il/', 'short' => 'Israel Bank'],
            'hu-mnb' => ['name' => esc_html__('Hungarian National Bank', 'exchange-rates'), 'country' => 'hu', 'source_url' => 'https://www.mnb.hu/', 'short' => 'MNB'],
            'ch-snb' => ['name' => esc_html__('Swiss National Bank', 'exchange-rates'), 'country' => 'ch', 'source_url' => 'https://www.snb.ch/', 'short' => 'Swiss Bank'],
            'tw-cbc' => ['name' => esc_html__('Central Bank of the Republic of China', 'exchange-rates'), 'country' => 'tw', 'source_url' => 'https://www.cbc.gov.tw/en/mp-2.html', 'short' => 'CBC TW'],
            'ph-bsp' => ['name' => esc_html__('Central Bank of the Philippines', 'exchange-rates'), 'country' => 'ph', 'source_url' => 'https://www.bsp.gov.ph/', 'short' => 'BSP PH'],
            'in-fbil' => ['name' => esc_html__('Financial Benchmarks India', 'exchange-rates'), 'country' => 'in', 'source_url' => 'https://www.fbil.org.in/', 'short' => 'FBIL'],
            'gb-boe' => ['name' => esc_html__('Bank of England', 'exchange-rates'), 'country' => 'gb', 'source_url' => 'https://www.bankofengland.co.uk/', 'short' => 'England Bank'],
            'et-nbe' => ['name' => esc_html__('National Bank of Ethiopia', 'exchange-rates'), 'country' => 'et', 'source_url' => 'https://nbe.gov.et/', 'short' => 'Ethiopia Bank'],
            'bt-rma' => ['name' => esc_html__('Royal Monetary Authority of Bhutan', 'exchange-rates'), 'country' => 'bt', 'source_url' => 'http://www.rma.org.bt/', 'short' => 'Bhutan Monetary'],
            'az-cbar' => ['name' => esc_html__('Central Bank of the Republic of Azerbaijan', 'exchange-rates'), 'country' => 'az', 'source_url' => 'https://www.cbar.az/', 'short' => 'CBAR AZ'],
            'au-rba' => ['name' => esc_html__('Reserve Bank of Australia', 'exchange-rates'), 'country' => 'au', 'source_url' => 'https://www.rba.gov.au/', 'short' => 'RBA AU'],
            'uz-cbu' => ['name' => esc_html__('The Central Bank of the Republic of Uzbekistan', 'exchange-rates'), 'country' => 'uz', 'source_url' => 'https://cbu.uz/', 'short' => 'CBU UZ'],
            'ng-cbn' => ['name' => esc_html__('Central Bank of Nigeria', 'exchange-rates'), 'country' => 'ng', 'source_url' => 'https://www.cbn.gov.ng/', 'short' => 'Nigeria Bank'],
            'kh-nbc' => ['name' => esc_html__('Bank of Cambodia', 'exchange-rates'), 'country' => 'kh', 'source_url' => 'https://www.nbc.org.kh/', 'short' => 'Cambodia Bank'],
            'sg-mas' => ['name' => esc_html__('Monetary Authority of Singapore', 'exchange-rates'), 'country' => 'sg', 'source_url' => 'https://www.mas.gov.sg/', 'short' => 'MAS SG'],
            'al-boa' => ['name' => esc_html__('Bank of Albania', 'exchange-rates'), 'country' => 'al', 'source_url' => 'https://www.bankofalbania.org/', 'short' => 'Albania Bank'],
            'mm-cbm' => ['name' => esc_html__('Central Bank of Myanmar', 'exchange-rates'), 'country' => 'mm', 'source_url' => 'https://www.cbm.gov.mm/', 'short' => 'Myanmar Bank'],
        ];
    }

    public static $_new0 = 'currencyconvert.online';
    public static $_new1 = 'currencyrate.today';
    public static $_new2 = 'moneyconvert.net';
}
