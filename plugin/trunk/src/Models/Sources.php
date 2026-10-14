<?php

namespace Dejurin\ExchangeRates\Models;

if (!defined('ABSPATH')) {
    exit;
}

class Sources
{
    public static function get_list()
    {
        return [
            'pe-yanki' => ['name' => 'Yanki', 'country' => 'pe', 'source_url' => 'https://yanki.pe/', 'short' => 'Yanki'],
            'tr-tcmb' => ['name' => 'Central Bank of the Republic of Turkey', 'country' => 'tr', 'source_url' => 'https://www.tcmb.gov.tr/', 'short' => 'TCMB'],
            'ch-snb' => ['name' => 'Swiss National Bank', 'country' => 'ch', 'source_url' => 'https://www.snb.ch/', 'short' => 'SNB'],
            'se-riksbank' => ['name' => 'Riksbank', 'country' => 'se', 'source_url' => 'https://www.riksbank.se/', 'short' => 'Riksbank'],
            'za-rb' => ['name' => 'South African Reserve Bank', 'country' => 'za', 'source_url' => 'https://www.resbank.co.za/', 'short' => 'Resbank'],
            'au-rba' => ['name' => 'Reserve Bank of Australia', 'country' => 'au', 'source_url' => 'https://www.rba.gov.au/', 'short' => 'RBA'],
            'pe-okanecambioonline' => ['name' => 'oKane Cambio Online', 'country' => 'pe', 'source_url' => 'https://www.okanecambioonline.com/', 'short' => 'oKane'],
            'np-nrb' => ['name' => 'Nepal Rastra bank', 'country' => 'np', 'source_url' => 'https://www.nrb.org.np/', 'short' => 'Rastra'],
            'tj-nbt' => ['name' => 'National Bank of Tajikistan', 'country' => 'tj', 'source_url' => 'https://www.nbt.tj/', 'short' => 'NBT'],
            'pl-nbp' => ['name' => 'Polish National Bank', 'country' => 'pl', 'source_url' => 'https://www.nbp.pl/', 'short' => 'NBP'],
            'kh-nbc' => ['name' => 'Bank of Cambodia', 'country' => 'kh', 'source_url' => 'https://www.nbc.org.kh/', 'short' => 'NBC'],
            'dk-dnb' => ['name' => 'Danmarks Nationalbank', 'country' => 'dk', 'source_url' => 'https://www.nationalbanken.dk/', 'short' => 'DK'],
            'kz-nbkz' => ['name' => 'National Bank of Kazakhstan', 'country' => 'kz', 'source_url' => 'https://www.nationalbank.kz/', 'short' => 'NBK'],
            'ng-nairablackmarket' => ['name' => 'Naira Black Market Exchange Rates Today', 'country' => 'ng', 'source_url' => 'https://ngn.currencyrate.today/usd', 'short' => 'Naira BM'],
            'ua-monobank' => ['name' => 'Monobank', 'country' => 'ua', 'source_url' => 'https://www.monobank.ua/', 'short' => 'Monobank'],
            'hu-mnb' => ['name' => 'Hungarian National Bank', 'country' => 'hu', 'source_url' => 'https://www.mnb.hu/', 'short' => 'MNB'],
            'sg-mas' => ['name' => 'Monetary Authority of Singapore', 'country' => 'sg', 'source_url' => 'https://www.mas.gov.sg/', 'short' => 'MAS'],
            'in-fbil' => ['name' => 'Financial Benchmarks India', 'country' => 'in', 'source_url' => 'https://www.fbil.org.in/', 'short' => 'FBIL'],
            'oecs-eccb' => ['name' => 'Eastern Caribbean Central Bank', 'country' => 'ecs', 'source_url' => 'https://www.eccb-centralbank.org/', 'short' => 'ECCB'],
            'eu-ecb' => ['name' => 'European Central Bank', 'country' => 'eu', 'source_url' => 'https://www.ecb.europa.eu/', 'short' => 'ECB'],
            'ar-cronistadollarblue' => ['name' => 'Cronista Dollar Blue', 'country' => 'ar', 'source_url' => 'https://ars.currencyrate.today/usd', 'short' => 'Dolar Blue'],
            'dz-cpa' => ['name' => 'Crédit Populaire d’Algérie', 'country' => 'dz', 'source_url' => 'https://www.cpa-bank.dz/', 'short' => 'CPA'],
            'cz-cnb' => ['name' => 'Czech National Bank', 'country' => 'cz', 'source_url' => 'https://www.cnb.cz/', 'short' => 'CNB'],
            'tm-cbt' => ['name' => 'Central Bank of Turkmenistan', 'country' => 'tm', 'source_url' => 'https://www.cbt.tm/', 'short' => 'CBT'],
            'ru-cbr' => ['name' => 'The Central Bank of the Russian Federation', 'country' => 'ru', 'source_url' => 'https://www.cbr.ru/', 'short' => 'CBR'],
            'pmr-cb' => ['name' => 'Transnistrian Republican Bank', 'country' => 'xx', 'source_url' => 'https://www.cbpmr.net/', 'short' => 'CBPMR'],
            'ng-cbn' => ['name' => 'Central Bank of Nigeria', 'country' => 'ng', 'source_url' => 'https://www.cbn.gov.ng/', 'short' => 'CBN'],
            'mm-cbm' => ['name' => 'Central Bank of Myanmar', 'country' => 'mm', 'source_url' => 'https://www.cbm.gov.mm/', 'short' => 'CBM'],
            'eg-cbe' => ['name' => 'Central Bank of Egypt', 'country' => 'eg', 'source_url' => 'https://www.cbe.org.eg/', 'short' => 'CBE'],
            'tw-cbc' => ['name' => 'Central Bank of the Republic of China', 'country' => 'tw', 'source_url' => 'https://www.cbc.gov.tw/', 'short' => 'CBC TW'],
            'ba-cbbh' => ['name' => 'The Central Bank of Bosnia and Herzegovina', 'country' => 'ba', 'source_url' => 'https://www.cbbh.ba/', 'short' => 'CBBH'],
            'bh-cbb' => ['name' => 'Central Bank of Bahrain', 'country' => 'bh', 'source_url' => 'https://www.cbb.gov.bh/', 'short' => 'CBB'],
            'az-cbar' => ['name' => 'Central Bank of the Republic of Azerbaijan', 'country' => 'az', 'source_url' => 'https://www.cbar.az/', 'short' => 'CBAR'],
            'is-cb' => ['name' => 'Central Bank of Iceland', 'country' => 'is', 'source_url' => 'https://www.cb.is/', 'short' => 'CBI'],
            'ph-bsp' => ['name' => 'Central Bank of the Philippines', 'country' => 'ph', 'source_url' => 'https://www.bsp.gov.ph/', 'short' => 'CBP'],
            'th-bot' => ['name' => 'Bank of Thailand', 'country' => 'th', 'source_url' => 'https://www.bot.or.th/', 'short' => 'BOK'],
            'il-boi' => ['name' => 'Bank of Israel', 'country' => 'il', 'source_url' => 'https://www.boi.org.il/', 'short' => 'BOI'],
            'ro-bnr' => ['name' => 'National Bank of Romania', 'country' => 'ro', 'source_url' => 'https://www.bnr.ro/', 'short' => 'NBR'],
            'md-bnm' => ['name' => 'National Bank of Moldova', 'country' => 'md', 'source_url' => 'https://www.bnm.md/', 'short' => 'NBM'],
            'bg-bnb' => ['name' => 'Bulgarian National Bank', 'country' => 'bg', 'source_url' => 'https://www.bnb.bg/', 'short' => 'BNB'],
            'ma-bkam' => ['name' => 'BANK AL-MAGHRIB', 'country' => 'ma', 'source_url' => 'https://www.bkam.ma/', 'short' => 'AL-MAGHRIB'],
            'lb-bdl' => ['name' => 'Bank of Lebanon', 'country' => 'lb', 'source_url' => 'https://www.bdl.gov.lb/', 'short' => 'Lebanon'],
            'tn-bct' => ['name' => 'Central Bank of Tunisia', 'country' => 'tn', 'source_url' => 'https://www.bct.gov.tn/', 'short' => 'Tunisia'],
            'cl-bc' => ['name' => 'Central Bank of Chile', 'country' => 'cl', 'source_url' => 'https://www.bcentral.cl/', 'short' => 'Chile'],
            'br-bcb' => ['name' => 'Banco Central do Brasil (USD)', 'country' => 'br', 'source_url' => 'https://www.bcb.gov.br/', 'short' => 'Brasil (USD)'],
            'cu-bc' => ['name' => 'Central Bank of Cuba', 'country' => 'cu', 'source_url' => 'https://www.bc.gob.cu/', 'short' => 'Cuba'],
            'mx-banxico-fix' => ['name' => 'Bank of Mexico (FIX)', 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/tipcamb/tipCamMIAction.do', 'short' => 'Banxico (FIX)'],
            'mx-banxico' => ['name' => 'Bank of Mexico', 'country' => 'mx', 'source_url' => 'https://www.banxico.org.mx/', 'short' => 'Banxico'],
            'gb-boe' => ['name' => 'Bank of England', 'country' => 'gb', 'source_url' => 'https://www.bankofengland.co.uk/', 'short' => 'BOE'],
            'ca-boc' => ['name' => 'Bank of Canada', 'country' => 'ca', 'source_url' => 'https://www.bankofcanada.ca/', 'short' => 'BOC'],
            'al-boa' => ['name' => 'Bank of Albania', 'country' => 'al', 'source_url' => 'https://www.bankofalbania.org/', 'short' => 'Albania'],
            'ro-bancatransilvania' => ['name' => 'Banca Transilvania', 'country' => 'ro', 'source_url' => 'https://www.bancatransilvania.ro/', 'short' => 'Transilvania'],
            'pe-tkambio' => ['name' => 'TKambio', 'country' => 'pe', 'source_url' => 'https://tkambio.com/', 'short' => 'TKambio'],
            'pe-srcambio' => ['name' => 'Srcambio', 'country' => 'pe', 'source_url' => 'https://srcambio.pe/', 'short' => 'Srcambio'],
            'pe-roblex' => ['name' => 'Roblex', 'country' => 'pe', 'source_url' => 'https://roblex.pe/', 'short' => 'Roblex'],
            'ua-privatbank' => ['name' => 'PrivatBank', 'country' => 'ua', 'source_url' => 'https://privatbank.ua/', 'short' => 'PrivatBank'],
            'vn-vietcombank' => ['name' => 'Vietcombank', 'country' => 'vn', 'source_url' => 'https://portal.vietcombank.com.vn/', 'short' => 'Vietcombank'],
            'rs-nbs' => ['name' => 'National Bank of Serbia', 'country' => 'rs', 'source_url' => 'https://nbs.rs/', 'short' => 'National Bank of Serbia'],
            'ge-nbg' => ['name' => 'The National Bank of Georgia', 'country' => 'ge', 'source_url' => 'https://nbg.gov.ge/', 'short' => 'Georgia'],
            'et-nbe' => ['name' => 'National Bank of Ethiopia', 'country' => 'et', 'source_url' => 'https://nbe.gov.et/', 'short' => 'Ethiopia'],
            'ua-minfin-blackmarket-usd' => ['name' => 'Minfin Blackmarket USD', 'country' => 'ua', 'source_url' => 'https://uah.currencyrate.today/usd', 'short' => 'Currency Black Market'],
            'lb-sayrafa' => ['name' => 'Lebanese Lira Sayrafa Rate', 'country' => 'lb', 'source_url' => 'https://lbp.currencyrate.today/usd', 'short' => 'Sayrafa Rate'],
            'lb-lbprate' => ['name' => 'Lebanese Lira Black Market', 'country' => 'lb', 'source_url' => 'https://lbp.currencyrate.today/usd', 'short' => 'Black Market'],
            'pe-instakash' => ['name' => 'Instakash FX', 'country' => 'pe', 'source_url' => 'https://instakash.net/', 'short' => 'Instakash FX'],
            'pe-hirpower' => ['name' => 'Hir Power', 'country' => 'pe', 'source_url' => 'https://hirpower.com/', 'short' => 'Hir Power'],
            'by-belta' => ['name' => 'National Bank of the Republic of Belarus (Belta)', 'country' => 'by', 'source_url' => 'https://eng.belta.by/', 'short' => 'Belta'],
            'pe-dollarhouse' => ['name' => 'Dollar House', 'country' => 'pe', 'source_url' => 'https://dollarhouse.pe/', 'short' => 'Dollar House'],
            'dz-devisesquare' => ['name' => 'Devise Square (Black Market)', 'country' => 'dz', 'source_url' => 'https://dzd.currencyrate.today/usd', 'short' => 'Devise Square'],
            'currencyrate' => ['name' => 'CurrencyRate.Today', 'country' => 'currencyrate', 'source_url' => 'https://currencyrate.today/', 'short' => 'CurrencyRate'],
            'ae-cbae' => ['name' => 'Central Bank of the UAE', 'country' => 'ae', 'source_url' => 'https://centralbank.ae/', 'short' => 'CBUAE'],
            'uz-cbu' => ['name' => 'The Central Bank of the Republic of Uzbekistan', 'country' => 'uz', 'source_url' => 'https://cbu.uz/', 'short' => 'CBU'],
            'pe-cambix' => ['name' => 'Cambix', 'country' => 'pe', 'source_url' => 'https://cambix.com.pe/', 'short' => 'Cambix'],
            'pe-cambiafx' => ['name' => 'Cambia FX', 'country' => 'pe', 'source_url' => 'https://cambiafx.pe/', 'short' => 'Cambia FX'],
            'pe-cambi' => ['name' => 'Cambi', 'country' => 'pe', 'source_url' => 'https://cambi.pe/', 'short' => 'Cambi'],
            'ss-boss' => ['name' => 'Bank of South Sudan', 'country' => 'ss', 'source_url' => 'https://boss.gov.ss/', 'short' => 'BSS'],
            'bd-bb' => ['name' => 'Bangladesh Bank', 'country' => 'bd', 'source_url' => 'https://bb.org.bd/', 'short' => 'Bangladesh Bank'],
            'ua-nbu' => ['name' => 'National Bank of Ukraine', 'country' => 'ua', 'source_url' => 'https://bank.gov.ua/', 'short' => 'NBU'],
            'amao-wama' => ['name' => 'West African Monetary Agency', 'country' => 'xx', 'source_url' => 'https://amao-wama.org/', 'short' => 'WAMA'],
            'kr-smbs' => ['name' => 'Seoul Money Brokerage Services', 'country' => 'kr', 'source_url' => 'http://www.smbs.biz/', 'short' => 'SMBS'],
            'bt-rma' => ['name' => 'Royal Monetary Authority of Bhutan', 'country' => 'bt', 'source_url' => 'http://www.rma.org.bt/', 'short' => 'RMA'],
            've-bcv' => ['name' => 'Central bank of Venezuela', 'country' => 've', 'source_url' => 'http://www.bcv.org.ve/', 'short' => 'Venezuela'],
            'ar-bcra-wholesale' => ['name' => 'Central Bank of Argentina (wholesale)', 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'CBA (wholesale)'],
            'ar-bcra-uva' => ['name' => 'Central Bank of Argentina (uva)', 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'CBA (uva)'],
            'ar-bcra-retail' => ['name' => 'Central Bank of Argentina (retail)', 'country' => 'ar', 'source_url' => 'http://www.bcra.gov.ar/', 'short' => 'CBA (retail)'],
            'eg-blackmarket' => ['name' => 'Egypt Black Market Currency Rate', 'country' => 'eg', 'source_url' => 'https://egp.currencyrate.today/usd', 'short' => 'Egypt Black Market'],
            'secondlife' => ['name' => 'Second Life Linden Dollar Exchange Rate', 'country' => 'xx', 'source_url' => 'https://ld.currencyrate.today/usd', 'short' => 'Linden Dollar'],
        ];
    }

    # Sources
    public static $_new0 = 'currencyconvert.online';
    public static $_new1 = 'currencyrate.today';
    public static $_new2 = 'currencyconvert.online';
}
