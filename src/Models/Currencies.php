<?php

namespace Dejurin\ExchangeRates\Models;

use Dejurin\ExchangeRates\Plugin;

class Currencies
{
    public static function get_list()
    {
        return [
            'AED' => [
                'flag' => 'AE',
                'name' => __('UAE Dirham', Plugin::PLUGIN_SLUG),
                'region' => __('UAE', Plugin::PLUGIN_SLUG),
            ],
            'AFN' => [
                'flag' => 'AF',
                'name' => __('Afghan Afghani', Plugin::PLUGIN_SLUG),
                'region' => __('Afghanistan', Plugin::PLUGIN_SLUG),
            ],
            'ALL' => [
                'flag' => 'AL',
                'name' => __('Albanian Lek', Plugin::PLUGIN_SLUG),
                'region' => __('Albania', Plugin::PLUGIN_SLUG),
            ],
            'AMD' => [
                'flag' => 'AM',
                'name' => __('Armenian Dram', Plugin::PLUGIN_SLUG),
                'region' => __('Armenia', Plugin::PLUGIN_SLUG),
            ],
            'ANG' => [
                'flag' => 'AN',
                'name' => __('Guilder', Plugin::PLUGIN_SLUG),
                'region' => __('Netherlands Antilles', Plugin::PLUGIN_SLUG),
            ],
            'AOA' => [
                'flag' => 'AO',
                'name' => __('Angolan Kwanza', Plugin::PLUGIN_SLUG),
                'region' => __('Angola', Plugin::PLUGIN_SLUG),
            ],
            'ARS' => [
                'flag' => 'AR',
                'name' => __('Argentine Peso', Plugin::PLUGIN_SLUG),
                'region' => __('Argentina', Plugin::PLUGIN_SLUG),
            ],
            'AUD' => [
                'flag' => 'AU',
                'name' => __('Australian Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Australia', Plugin::PLUGIN_SLUG),
            ],
            'AWG' => [
                'flag' => 'AW',
                'name' => __('Aruban Florin', Plugin::PLUGIN_SLUG),
                'region' => __('Aruba', Plugin::PLUGIN_SLUG),
            ],
            'AZN' => [
                'flag' => 'AZ',
                'name' => __('Azerbaijanian Manat', Plugin::PLUGIN_SLUG),
                'region' => __('Azerbaijan', Plugin::PLUGIN_SLUG),
            ],
            'BAM' => [
                'flag' => 'BA',
                'name' => __('Convertible Mark', Plugin::PLUGIN_SLUG),
                'region' => __('Bosnia & Herzegovina', Plugin::PLUGIN_SLUG),
            ],
            'BBD' => [
                'flag' => 'BB',
                'name' => __('Barbados Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Barbados', Plugin::PLUGIN_SLUG),
            ],
            'BDT' => [
                'flag' => 'BD',
                'name' => __('Bangladeshi Taka', Plugin::PLUGIN_SLUG),
                'region' => __('Bangladesh', Plugin::PLUGIN_SLUG),
            ],
            'BGN' => [
                'flag' => 'BG',
                'name' => __('Bulgarian Lev', Plugin::PLUGIN_SLUG),
                'region' => __('Bulgaria', Plugin::PLUGIN_SLUG),
            ],
            'BHD' => [
                'flag' => 'BH',
                'name' => __('Bahraini Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Bahrain', Plugin::PLUGIN_SLUG),
            ],
            'BIF' => [
                'flag' => 'BI',
                'name' => __('Burundi Franc', Plugin::PLUGIN_SLUG),
                'region' => __('Burundi', Plugin::PLUGIN_SLUG),
            ],
            'BMD' => [
                'flag' => 'BM',
                'name' => __('Bermudian Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Bermuda', Plugin::PLUGIN_SLUG),
            ],
            'BND' => [
                'flag' => 'BN',
                'name' => __('Brunei Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Brunei Darussalam', Plugin::PLUGIN_SLUG),
            ],
            'BOB' => [
                'flag' => 'BO',
                'name' => __('Boliviano', Plugin::PLUGIN_SLUG),
                'region' => __('Bolivia', Plugin::PLUGIN_SLUG),
            ],
            'BRL' => [
                'flag' => 'BR',
                'name' => __('Brazilian Real', Plugin::PLUGIN_SLUG),
                'region' => __('Brazil', Plugin::PLUGIN_SLUG),
            ],
            'BSD' => [
                'flag' => 'BS',
                'name' => __('Bahamian Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('The Bahamas', Plugin::PLUGIN_SLUG),
            ],
            'BTC' => [
                'flag' => 'BTC',
                'name' => __('Bitcoin', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'ETH' => [
                'flag' => 'ETH',
                'name' => __('Ethereum', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'LTC' => [
                'flag' => 'LTC',
                'name' => __('Litecoin', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'DOGE' => [
                'flag' => 'DOGE',
                'name' => __('Doge', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'DASH' => [
                'flag' => 'DASH',
                'name' => __('Dash', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'NXT' => [
                'flag' => 'NXT',
                'name' => __('NXT', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'STR' => [
                'flag' => 'STR',
                'name' => __('STR', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'XRP' => [
                'flag' => 'XRP',
                'name' => __('Ripple', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'XMR' => [
                'flag' => 'XMR',
                'name' => __('Monero', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'NMC' => [
                'flag' => 'NMC',
                'name' => __('NMC', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'PPC' => [
                'flag' => 'PPC',
                'name' => __('PPC', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'NVC' => [
                'flag' => 'NVC',
                'name' => __('NVC', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'XPM' => [
                'flag' => 'XPM',
                'name' => __('XPM', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'EAC' => [
                'flag' => 'EAC',
                'name' => __('EAC', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'VTC' => [
                'flag' => 'VTC',
                'name' => __('VTC', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'EMC' => [
                'flag' => 'EMC',
                'name' => __('EMC', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'FCT' => [
                'flag' => 'FCT',
                'name' => __('FCT', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'BTN' => [
                'flag' => 'BT',
                'name' => __('Bhutanese Ngultrum', Plugin::PLUGIN_SLUG),
                'region' => __('Bhutan', Plugin::PLUGIN_SLUG),
            ],
            'BWP' => [
                'flag' => 'BW',
                'name' => __('Botswana Pula', Plugin::PLUGIN_SLUG),
                'region' => __('Botswana', Plugin::PLUGIN_SLUG),
            ],
            'BYN' => [
                'flag' => 'BY',
                'name' => __('Belarusian ruble', Plugin::PLUGIN_SLUG),
                'region' => __('Belarus', Plugin::PLUGIN_SLUG),
            ],
            'BTS' => [
                'flag' => 'BTS',
                'name' => __('BitShares', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
            'BYR' => [
                'flag' => 'BY',
                'name' => __('Belarussian Ruble', Plugin::PLUGIN_SLUG),
                'region' => __('Belarus', Plugin::PLUGIN_SLUG),
            ],
            'BZD' => [
                'flag' => 'BZ',
                'name' => __('Belize Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Belize', Plugin::PLUGIN_SLUG),
            ],
            'CAD' => [
                'flag' => 'CA',
                'name' => __('Canadian Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Canada', Plugin::PLUGIN_SLUG),
            ],
            'CDF' => [
                'flag' => 'CD',
                'name' => __('Congolese Franc', Plugin::PLUGIN_SLUG),
                'region' => __('DR Congo', Plugin::PLUGIN_SLUG),
            ],
            'CHF' => [
                'flag' => 'CH',
                'name' => __('Swiss Franc', Plugin::PLUGIN_SLUG),
                'region' => __('Switzerland', Plugin::PLUGIN_SLUG),
            ],
            'CLP' => [
                'flag' => 'CL',
                'name' => __('Chilean Peso', Plugin::PLUGIN_SLUG),
                'region' => __('Chile', Plugin::PLUGIN_SLUG),
            ],
            'CLF' => [
                'flag' => 'CL',
                'name' => __('Chilean Unit of Account', Plugin::PLUGIN_SLUG),
                'region' => __('Chile', Plugin::PLUGIN_SLUG),
            ],
            'CNY' => [
                'flag' => 'CN',
                'name' => __('Renminbi', Plugin::PLUGIN_SLUG),
                'region' => __('China', Plugin::PLUGIN_SLUG),
            ],
            'CNH' => [
                'flag' => 'CN',
                'name' => __('Chinese Yuan (offshore)', Plugin::PLUGIN_SLUG),
                'region' => __('China', Plugin::PLUGIN_SLUG),
            ],
            'COP' => [
                'flag' => 'CO',
                'name' => __('Colombian Peso', Plugin::PLUGIN_SLUG),
                'region' => __('Colombia', Plugin::PLUGIN_SLUG),
            ],
            'CRC' => [
                'flag' => 'CR',
                'name' => __('Costa Rican Colon', Plugin::PLUGIN_SLUG),
                'region' => __('Costa Rica', Plugin::PLUGIN_SLUG),
            ],
            'CUP' => [
                'flag' => 'CU',
                'name' => __('Cuban Peso', Plugin::PLUGIN_SLUG),
                'region' => __('Cuba', Plugin::PLUGIN_SLUG),
            ],
            'CUC' => [
                'flag' => 'CU',
                'name' => __('Convertible peso', Plugin::PLUGIN_SLUG),
                'region' => __('Cuba', Plugin::PLUGIN_SLUG),
            ],
            'CVE' => [
                'flag' => 'CV',
                'name' => __('Cabo Verde Escudo', Plugin::PLUGIN_SLUG),
                'region' => __('Cape Verde', Plugin::PLUGIN_SLUG),
            ],
            'CZK' => [
                'flag' => 'CZ',
                'name' => __('Czech Koruna', Plugin::PLUGIN_SLUG),
                'region' => __('Czech Republic', Plugin::PLUGIN_SLUG),
            ],
            'DJF' => [
                'flag' => 'DJ',
                'name' => __('Djibouti Franc', Plugin::PLUGIN_SLUG),
                'region' => __('Djibouti', Plugin::PLUGIN_SLUG),
            ],
            'DKK' => [
                'flag' => 'DK',
                'name' => __('Danish Krone', Plugin::PLUGIN_SLUG),
                'region' => __('Denmark', Plugin::PLUGIN_SLUG),
            ],
            'DOP' => [
                'flag' => 'DO',
                'name' => __('Dominican Peso', Plugin::PLUGIN_SLUG),
                'region' => __('Dominican Republic', Plugin::PLUGIN_SLUG),
            ],
            'DZD' => [
                'flag' => 'DZ',
                'name' => __('Algerian Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Algeria', Plugin::PLUGIN_SLUG),
            ],
            'EGP' => [
                'flag' => 'EG',
                'name' => __('Egyptian Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Egypt', Plugin::PLUGIN_SLUG),
            ],
            'ERN' => [
                'flag' => 'ER',
                'name' => __('Eritrean Nakfa', Plugin::PLUGIN_SLUG),
                'region' => __('Eritrea', Plugin::PLUGIN_SLUG),
            ],
            'ETB' => [
                'flag' => 'ET',
                'name' => __('Ethiopian Birr', Plugin::PLUGIN_SLUG),
                'region' => __('Ethiopia', Plugin::PLUGIN_SLUG),
            ],
            'EUR' => [
                'flag' => 'EU',
                'name' => __('Euro', Plugin::PLUGIN_SLUG),
                'region' => __('European Union', Plugin::PLUGIN_SLUG),
            ],
            'FJD' => [
                'flag' => 'FJ',
                'name' => __('Fiji Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Fiji', Plugin::PLUGIN_SLUG),
            ],
            'FKP' => [
                'flag' => 'FK',
                'name' => __('Falkland Islands Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Falkland Islands', Plugin::PLUGIN_SLUG),
            ],
            'GBP' => [
                'flag' => 'GB',
                'name' => __('British Pound', Plugin::PLUGIN_SLUG),
                'region' => __('United Kingdom', Plugin::PLUGIN_SLUG),
            ],
            'IMP' => [
                'flag' => 'IM',
                'name' => __('Manx pound', Plugin::PLUGIN_SLUG),
                'region' => __('Isle of Man', Plugin::PLUGIN_SLUG),
            ],
            'GEL' => [
                'flag' => 'GE',
                'name' => __('Georgian Lari', Plugin::PLUGIN_SLUG),
                'region' => __('Georgia', Plugin::PLUGIN_SLUG),
            ],
            'GGP' => [
                'flag' => 'GG',
                'name' => __('Guernsey Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Guernsey', Plugin::PLUGIN_SLUG),
            ],
            'GHS' => [
                'flag' => 'GH',
                'name' => __('Ghana Cedi', Plugin::PLUGIN_SLUG),
                'region' => __('Ghana', Plugin::PLUGIN_SLUG),
            ],
            'GIP' => [
                'flag' => 'GI',
                'name' => __('Gibraltar Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Gibraltar', Plugin::PLUGIN_SLUG),
            ],
            'GMD' => [
                'flag' => 'GM',
                'name' => __('Gambian Dalasi', Plugin::PLUGIN_SLUG),
                'region' => __('The Gambia', Plugin::PLUGIN_SLUG),
            ],
            'GNF' => [
                'flag' => 'GN',
                'name' => __('Guinea Franc', Plugin::PLUGIN_SLUG),
                'region' => __('Guinea', Plugin::PLUGIN_SLUG),
            ],
            'GTQ' => [
                'flag' => 'GT',
                'name' => __('Guatemalan Quetzal', Plugin::PLUGIN_SLUG),
                'region' => __('Guatemala', Plugin::PLUGIN_SLUG),
            ],
            'GYD' => [
                'flag' => 'GY',
                'name' => __('Guyana Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Guyana', Plugin::PLUGIN_SLUG),
            ],
            'HKD' => [
                'flag' => 'HK',
                'name' => __('Hong Kong Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Hong Kong', Plugin::PLUGIN_SLUG),
            ],
            'HNL' => [
                'flag' => 'HN',
                'name' => __('Honduran Lempira', Plugin::PLUGIN_SLUG),
                'region' => __('Honduras', Plugin::PLUGIN_SLUG),
            ],
            'HRK' => [
                'flag' => 'HR',
                'name' => __('Croatian Kuna', Plugin::PLUGIN_SLUG),
                'region' => __('Croatia', Plugin::PLUGIN_SLUG),
            ],
            'HTG' => [
                'flag' => 'HT',
                'name' => __('Haitian Gourde', Plugin::PLUGIN_SLUG),
                'region' => __('Haiti', Plugin::PLUGIN_SLUG),
            ],
            'HUF' => [
                'flag' => 'HU',
                'name' => __('Hungarian Forint', Plugin::PLUGIN_SLUG),
                'region' => __('Hungary', Plugin::PLUGIN_SLUG),
            ],
            'IDR' => [
                'flag' => 'ID',
                'name' => __('Indonesian Rupiah', Plugin::PLUGIN_SLUG),
                'region' => __('Indonesia', Plugin::PLUGIN_SLUG),
            ],
            'ILS' => [
                'flag' => 'IL',
                'name' => __('Israeli Shekel', Plugin::PLUGIN_SLUG),
                'region' => __('Israel', Plugin::PLUGIN_SLUG),
            ],
            'INR' => [
                'flag' => 'IN',
                'name' => __('Indian Rupee', Plugin::PLUGIN_SLUG),
                'region' => __('India', Plugin::PLUGIN_SLUG),
            ],
            'IQD' => [
                'flag' => 'IQ',
                'name' => __('Iraqi Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Iraq', Plugin::PLUGIN_SLUG),
            ],
            'IRR' => [
                'flag' => 'IR',
                'name' => __('Iranian Rial', Plugin::PLUGIN_SLUG),
                'region' => __('Iran', Plugin::PLUGIN_SLUG),
            ],
            'ISK' => [
                'flag' => 'IS',
                'name' => __('Iceland Krona', Plugin::PLUGIN_SLUG),
                'region' => __('Iceland', Plugin::PLUGIN_SLUG),
            ],
            'JEP' => [
                'flag' => 'JE',
                'name' => __('Jersey Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Jersey', Plugin::PLUGIN_SLUG),
            ],
            'JMD' => [
                'flag' => 'JM',
                'name' => __('Jamaican Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Jamaica', Plugin::PLUGIN_SLUG),
            ],
            'JOD' => [
                'flag' => 'JO',
                'name' => __('Jordanian Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Jordan', Plugin::PLUGIN_SLUG),
            ],
            'JPY' => [
                'flag' => 'JP',
                'name' => __('Japanese Yen', Plugin::PLUGIN_SLUG),
                'region' => __('Japan', Plugin::PLUGIN_SLUG),
            ],
            'KES' => [
                'flag' => 'KE',
                'name' => __('Kenyan Shilling', Plugin::PLUGIN_SLUG),
                'region' => __('Kenya', Plugin::PLUGIN_SLUG),
            ],
            'KGS' => [
                'flag' => 'KG',
                'name' => __('Kyrgyzstani Som', Plugin::PLUGIN_SLUG),
                'region' => __('Kyrgyzstan', Plugin::PLUGIN_SLUG),
            ],
            'KHR' => [
                'flag' => 'KH',
                'name' => __('Cambodian Riel', Plugin::PLUGIN_SLUG),
                'region' => __('Cambodia', Plugin::PLUGIN_SLUG),
            ],
            'KMF' => [
                'flag' => 'KM',
                'name' => __('Comoro Franc', Plugin::PLUGIN_SLUG),
                'region' => __('The Comoros', Plugin::PLUGIN_SLUG),
            ],
            'KPW' => [
                'flag' => 'KP',
                'name' => __('North Korean Won', Plugin::PLUGIN_SLUG),
                'region' => __('North Korea', Plugin::PLUGIN_SLUG),
            ],
            'KRW' => [
                'flag' => 'KR',
                'name' => __('South Korean Won', Plugin::PLUGIN_SLUG),
                'region' => __('South Korea', Plugin::PLUGIN_SLUG),
            ],
            'KWD' => [
                'flag' => 'KW',
                'name' => __('Kuwaiti Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Kuwait', Plugin::PLUGIN_SLUG),
            ],
            'KYD' => [
                'flag' => 'KY',
                'name' => __('Cayman Islands Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('The Cayman Islands', Plugin::PLUGIN_SLUG),
            ],
            'KZT' => [
                'flag' => 'KZ',
                'name' => __('Kazakhstani Tenge', Plugin::PLUGIN_SLUG),
                'region' => __('Kazakhstan', Plugin::PLUGIN_SLUG),
            ],
            'LAK' => [
                'flag' => 'LA',
                'name' => __('Lao Kip', Plugin::PLUGIN_SLUG),
                'region' => __('Laos', Plugin::PLUGIN_SLUG),
            ],
            'LBP' => [
                'flag' => 'LB',
                'name' => __('Lebanese Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Lebanon', Plugin::PLUGIN_SLUG),
            ],
            'LKR' => [
                'flag' => 'LK',
                'name' => __('Sri Lanka Rupee', Plugin::PLUGIN_SLUG),
                'region' => __('Sri Lanka', Plugin::PLUGIN_SLUG),
            ],
            'LRD' => [
                'flag' => 'LR',
                'name' => __('Liberian Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Liberia', Plugin::PLUGIN_SLUG),
            ],
            'LSL' => [
                'flag' => 'LS',
                'name' => __('Lesotho Loti', Plugin::PLUGIN_SLUG),
                'region' => __('Lesotho', Plugin::PLUGIN_SLUG),
            ],
            'LYD' => [
                'flag' => 'LY',
                'name' => __('Libyan Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Libya', Plugin::PLUGIN_SLUG),
            ],
            'MAD' => [
                'flag' => 'MA',
                'name' => __('Moroccan Dirham', Plugin::PLUGIN_SLUG),
                'region' => __('Morocco', Plugin::PLUGIN_SLUG),
            ],
            'MDL' => [
                'flag' => 'MD',
                'name' => __('Moldovan Leu', Plugin::PLUGIN_SLUG),
                'region' => __('Moldova', Plugin::PLUGIN_SLUG),
            ],
            'MGA' => [
                'flag' => 'MG',
                'name' => __('Malagasy Ariary', Plugin::PLUGIN_SLUG),
                'region' => __('Madagascar', Plugin::PLUGIN_SLUG),
            ],
            'MKD' => [
                'flag' => 'MK',
                'name' => __('Macedonian Denar', Plugin::PLUGIN_SLUG),
                'region' => __('Macedonia', Plugin::PLUGIN_SLUG),
            ],
            'MMK' => [
                'flag' => 'MM',
                'name' => __('Myanmar Kyat', Plugin::PLUGIN_SLUG),
                'region' => __('Myanmar', Plugin::PLUGIN_SLUG),
            ],
            'MNT' => [
                'flag' => 'MN',
                'name' => __('Mongolian Tugrik', Plugin::PLUGIN_SLUG),
                'region' => __('Mongolia', Plugin::PLUGIN_SLUG),
            ],
            'MOP' => [
                'flag' => 'MO',
                'name' => __('Macanese Pataca', Plugin::PLUGIN_SLUG),
                'region' => __('Macao', Plugin::PLUGIN_SLUG),
            ],
            'MRO' => [
                'flag' => 'MR',
                'name' => __('Mauritanian Ouguiya', Plugin::PLUGIN_SLUG),
                'region' => __('Mauritania', Plugin::PLUGIN_SLUG),
            ],
            'MRU' => [
                'flag' => 'MR',
                'name' => __('Mauritanian Ouguiya', Plugin::PLUGIN_SLUG),
                'region' => __('Mauritania', Plugin::PLUGIN_SLUG),
            ],
            'MUR' => [
                'flag' => 'MU',
                'name' => __('Mauritius Rupee', Plugin::PLUGIN_SLUG),
                'region' => __('Mauritius', Plugin::PLUGIN_SLUG),
            ],
            'MVR' => [
                'flag' => 'MV',
                'name' => __('Maldivian Rufiyaa', Plugin::PLUGIN_SLUG),
                'region' => __('Maldives', Plugin::PLUGIN_SLUG),
            ],
            'MWK' => [
                'flag' => 'MW',
                'name' => __('Malawian Kwacha', Plugin::PLUGIN_SLUG),
                'region' => __('Malawi', Plugin::PLUGIN_SLUG),
            ],
            'MXN' => [
                'flag' => 'MX',
                'name' => __('Mexican Peso', Plugin::PLUGIN_SLUG),
                'region' => __('Mexico', Plugin::PLUGIN_SLUG),
            ],
            'MYR' => [
                'flag' => 'MY',
                'name' => __('Malaysian Ringgit', Plugin::PLUGIN_SLUG),
                'region' => __('Malaysia', Plugin::PLUGIN_SLUG),
            ],
            'MZN' => [
                'flag' => 'MZ',
                'name' => __('Mozambique Metical', Plugin::PLUGIN_SLUG),
                'region' => __('Mozambique', Plugin::PLUGIN_SLUG),
            ],
            'NAD' => [
                'flag' => 'NA',
                'name' => __('Namibia Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Namibia', Plugin::PLUGIN_SLUG),
            ],
            'NGN' => [
                'flag' => 'NG',
                'name' => __('Nigerian Naira', Plugin::PLUGIN_SLUG),
                'region' => __('Nigeria', Plugin::PLUGIN_SLUG),
            ],
            'NIO' => [
                'flag' => 'NI',
                'name' => __('Cordoba Oro', Plugin::PLUGIN_SLUG),
                'region' => __('Nicaragua', Plugin::PLUGIN_SLUG),
            ],
            'NOK' => [
                'flag' => 'NO',
                'name' => __('Norwegian Krone', Plugin::PLUGIN_SLUG),
                'region' => __('Norway', Plugin::PLUGIN_SLUG),
            ],
            'NPR' => [
                'flag' => 'NP',
                'name' => __('Nepalese Rupee', Plugin::PLUGIN_SLUG),
                'region' => __('Nepal', Plugin::PLUGIN_SLUG),
            ],
            'NZD' => [
                'flag' => 'NZ',
                'name' => __('New Zealand Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('New Zealand', Plugin::PLUGIN_SLUG),
            ],
            'OMR' => [
                'flag' => 'OM',
                'name' => __('Rial Omani', Plugin::PLUGIN_SLUG),
                'region' => __('Oman', Plugin::PLUGIN_SLUG),
            ],
            'PAB' => [
                'flag' => 'PA',
                'name' => __('Panamanian Balboa', Plugin::PLUGIN_SLUG),
                'region' => __('Panama', Plugin::PLUGIN_SLUG),
            ],
            'PEN' => [
                'flag' => 'PE',
                'name' => __('Peruvian Sol', Plugin::PLUGIN_SLUG),
                'region' => __('Peru', Plugin::PLUGIN_SLUG),
            ],
            'PGK' => [
                'flag' => 'PG',
                'name' => __('Papua New Guinean Kina', Plugin::PLUGIN_SLUG),
                'region' => __('Papua New Guinea', Plugin::PLUGIN_SLUG),
            ],
            'PHP' => [
                'flag' => 'PH',
                'name' => __('Philippine Peso', Plugin::PLUGIN_SLUG),
                'region' => __('Philippines', Plugin::PLUGIN_SLUG),
            ],
            'PKR' => [
                'flag' => 'PK',
                'name' => __('Pakistani Rupee', Plugin::PLUGIN_SLUG),
                'region' => __('Pakistan', Plugin::PLUGIN_SLUG),
            ],
            'PLN' => [
                'flag' => 'PL',
                'name' => __('Polish Zloty', Plugin::PLUGIN_SLUG),
                'region' => __('Poland', Plugin::PLUGIN_SLUG),
            ],
            'PYG' => [
                'flag' => 'PY',
                'name' => __('Paraguayan Guarani', Plugin::PLUGIN_SLUG),
                'region' => __('Paraguay', Plugin::PLUGIN_SLUG),
            ],
            'QAR' => [
                'flag' => 'QA',
                'name' => __('Qatari Rial', Plugin::PLUGIN_SLUG),
                'region' => __('Qatar', Plugin::PLUGIN_SLUG),
            ],
            'RON' => [
                'flag' => 'RO',
                'name' => __('Romanian Leu', Plugin::PLUGIN_SLUG),
                'region' => __('Romania', Plugin::PLUGIN_SLUG),
            ],
            'RSD' => [
                'flag' => 'RS',
                'name' => __('Serbian Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Serbia', Plugin::PLUGIN_SLUG),
            ],
            'RUB' => [
                'flag' => 'RU',
                'name' => __('Russian Ruble', Plugin::PLUGIN_SLUG),
                'region' => __('Russia', Plugin::PLUGIN_SLUG),
            ],
            'PRB' => [
                'flag' => 'XX',
                'name' => __('Transnistrian ruble', Plugin::PLUGIN_SLUG),
                'region' => __('Transnistria', Plugin::PLUGIN_SLUG),
            ],
            'RUR' => [
                'flag' => 'XX',
                'name' => __('USSR Ruble', Plugin::PLUGIN_SLUG),
                'region' => __('USSR', Plugin::PLUGIN_SLUG),
            ],
            'RWF' => [
                'flag' => 'RW',
                'name' => __('Rwanda Franc', Plugin::PLUGIN_SLUG),
                'region' => __('Rwanda', Plugin::PLUGIN_SLUG),
            ],
            'SAR' => [
                'flag' => 'SA',
                'name' => __('Saudi Riyal', Plugin::PLUGIN_SLUG),
                'region' => __('Saudi Arabia', Plugin::PLUGIN_SLUG),
            ],
            'SBD' => [
                'flag' => 'SB',
                'name' => __('Solomon Islands Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Solomon Islands', Plugin::PLUGIN_SLUG),
            ],
            'SCR' => [
                'flag' => 'SC',
                'name' => __('Seychelles Rupee', Plugin::PLUGIN_SLUG),
                'region' => __('Seychelles', Plugin::PLUGIN_SLUG),
            ],
            'SDG' => [
                'flag' => 'SD',
                'name' => __('Sudanese Pound', Plugin::PLUGIN_SLUG),
                'region' => __('The Sudan', Plugin::PLUGIN_SLUG),
            ],
            'SEK' => [
                'flag' => 'SE',
                'name' => __('Swedish Krona', Plugin::PLUGIN_SLUG),
                'region' => __('Sweden', Plugin::PLUGIN_SLUG),
            ],
            'SGD' => [
                'flag' => 'SG',
                'name' => __('Singapore Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Singapore', Plugin::PLUGIN_SLUG),
            ],
            'SHP' => [
                'flag' => 'SH',
                'name' => __('Saint Helena Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Saint Helena', Plugin::PLUGIN_SLUG),
            ],
            'SLL' => [
                'flag' => 'SL',
                'name' => __('Sierra Leonean Leone', Plugin::PLUGIN_SLUG),
                'region' => __('Sierra Leone', Plugin::PLUGIN_SLUG),
            ],
            'SOS' => [
                'flag' => 'SO',
                'name' => __('Somali Shilling', Plugin::PLUGIN_SLUG),
                'region' => __('Somalia', Plugin::PLUGIN_SLUG),
            ],
            'SRD' => [
                'flag' => 'SR',
                'name' => __('Surinam Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Suriname', Plugin::PLUGIN_SLUG),
            ],
            'STD' => [
                'flag' => 'ST',
                'name' => __('Sao Tomean Dobra', Plugin::PLUGIN_SLUG),
                'region' => __('Sao Tome And Principe', Plugin::PLUGIN_SLUG),
            ],
            'STN' => [
                'flag' => 'ST',
                'name' => __('Sao Tomean Dobra', Plugin::PLUGIN_SLUG),
                'region' => __('Sao Tome And Principe', Plugin::PLUGIN_SLUG),
            ],
            'SVC' => [
                'flag' => 'SV',
                'name' => __('El Salvador Colon', Plugin::PLUGIN_SLUG),
                'region' => __('El Salvador', Plugin::PLUGIN_SLUG),
            ],
            'SYP' => [
                'flag' => 'SY',
                'name' => __('Syrian Pound', Plugin::PLUGIN_SLUG),
                'region' => __('Syrian Arab Republic', Plugin::PLUGIN_SLUG),
            ],
            'SSP' => [
                'flag' => 'SS',
                'name' => __('South Sudanese Pound', Plugin::PLUGIN_SLUG),
                'region' => __('South Sudan,', Plugin::PLUGIN_SLUG),
            ],
            'SZL' => [
                'flag' => 'SZ',
                'name' => __('Swazi Lilangeni', Plugin::PLUGIN_SLUG),
                'region' => __('Swaziland', Plugin::PLUGIN_SLUG),
            ],
            'THB' => [
                'flag' => 'TH',
                'name' => __('Thai Baht', Plugin::PLUGIN_SLUG),
                'region' => __('Thailand', Plugin::PLUGIN_SLUG),
            ],
            'TJS' => [
                'flag' => 'TJ',
                'name' => __('Tajikistani Somoni', Plugin::PLUGIN_SLUG),
                'region' => __('Tajikistan', Plugin::PLUGIN_SLUG),
            ],
            'TMT' => [
                'flag' => 'TM',
                'name' => __('Turkmenistan New Manat', Plugin::PLUGIN_SLUG),
                'region' => __('Turkmenistan', Plugin::PLUGIN_SLUG),
            ],
            'TND' => [
                'flag' => 'TN',
                'name' => __('Tunisian Dinar', Plugin::PLUGIN_SLUG),
                'region' => __('Tunisia', Plugin::PLUGIN_SLUG),
            ],
            'TOP' => [
                'flag' => 'TO',
                'name' => __('Tongan Pa?anga', Plugin::PLUGIN_SLUG),
                'region' => __('Tonga', Plugin::PLUGIN_SLUG),
            ],
            'TRY' => [
                'flag' => 'TR',
                'name' => __('Turkish Lira', Plugin::PLUGIN_SLUG),
                'region' => __('Turkey', Plugin::PLUGIN_SLUG),
            ],
            'TTD' => [
                'flag' => 'TT',
                'name' => __('Trinidad and Tobago Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Trinidad And Tobago', Plugin::PLUGIN_SLUG),
            ],
            'TWD' => [
                'flag' => 'TW',
                'name' => __('New Taiwan Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Taiwan', Plugin::PLUGIN_SLUG),
            ],
            'TZS' => [
                'flag' => 'TZ',
                'name' => __('Tanzanian Shilling', Plugin::PLUGIN_SLUG),
                'region' => __('Tanzania', Plugin::PLUGIN_SLUG),
            ],
            'UAH' => [
                'flag' => 'UA',
                'name' => __('Ukrainian Hryvnia', Plugin::PLUGIN_SLUG),
                'region' => __('Ukraine', Plugin::PLUGIN_SLUG),
            ],
            'UGX' => [
                'flag' => 'UG',
                'name' => __('Uganda Shilling', Plugin::PLUGIN_SLUG),
                'region' => __('Uganda', Plugin::PLUGIN_SLUG),
            ],
            'USD' => [
                'flag' => 'US',
                'name' => __('US Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('United States', Plugin::PLUGIN_SLUG),
            ],
            'UYU' => [
                'flag' => 'UY',
                'name' => __('Peso Uruguayo', Plugin::PLUGIN_SLUG),
                'region' => __('Uruguay', Plugin::PLUGIN_SLUG),
            ],
            'UZS' => [
                'flag' => 'UZ',
                'name' => __('Uzbekistan Sum', Plugin::PLUGIN_SLUG),
                'region' => __('Uzbekistan', Plugin::PLUGIN_SLUG),
            ],
            'VEF' => [
                'flag' => 'VE',
                'name' => __('Venezuelan Bolivar', Plugin::PLUGIN_SLUG),
                'region' => __('Venezuela', Plugin::PLUGIN_SLUG),
            ],
            'VES' => [
                'flag' => 'VE',
                'name' => __('Venezuelan Bolivar', Plugin::PLUGIN_SLUG),
                'region' => __('Venezuela', Plugin::PLUGIN_SLUG),
            ],
            'VEF_DIPRO' => [
                'flag' => 'VE',
                'name' => __('VEF DIPRO', Plugin::PLUGIN_SLUG),
                'region' => __('Venezuela', Plugin::PLUGIN_SLUG),
            ],
            'VEF_DICOM' => [
                'flag' => 'VE',
                'name' => __('VEF DICOM', Plugin::PLUGIN_SLUG),
                'region' => __('Venezuela', Plugin::PLUGIN_SLUG),
            ],
            'VEF_BLKMKT' => [
                'flag' => 'VE',
                'name' => __('VEF BLKMKT', Plugin::PLUGIN_SLUG),
                'region' => __('Venezuela', Plugin::PLUGIN_SLUG),
            ],
            'VND' => [
                'flag' => 'VN',
                'name' => __('Vietnamese Dong', Plugin::PLUGIN_SLUG),
                'region' => __('Vietnam', Plugin::PLUGIN_SLUG),
            ],
            'VUV' => [
                'flag' => 'VU',
                'name' => __('Vanuatu Vatu', Plugin::PLUGIN_SLUG),
                'region' => __('Vanuatu', Plugin::PLUGIN_SLUG),
            ],
            'WST' => [
                'flag' => 'WS',
                'name' => __('Samoan Tala', Plugin::PLUGIN_SLUG),
                'region' => __('Samoa', Plugin::PLUGIN_SLUG),
            ],
            'XAF' => [
                'flag' => 'CF',
                'name' => __('CFA Franc BEAC', Plugin::PLUGIN_SLUG),
                'region' => __('Central African States', Plugin::PLUGIN_SLUG),
            ],
            'XAU' => [
                'flag' => 'XAU',
                'name' => __('Gold (ounce)', Plugin::PLUGIN_SLUG),
                'region' => __('&ndash;', Plugin::PLUGIN_SLUG),
            ],
            'XAG' => [
                'flag' => 'XAG',
                'name' => __('Silver (ounce)', Plugin::PLUGIN_SLUG),
                'region' => __('&ndash;', Plugin::PLUGIN_SLUG),
            ],
            'XPD' => [
                'flag' => 'XPD',
                'name' => __('Palladium (ounce)', Plugin::PLUGIN_SLUG),
                'region' => __('&ndash;', Plugin::PLUGIN_SLUG),
            ],
            'XPT' => [
                'flag' => 'XPT',
                'name' => __('Platinum (ounce)', Plugin::PLUGIN_SLUG),
                'region' => __('&ndash;', Plugin::PLUGIN_SLUG),
            ],
            'XCD' => [
                'flag' => 'XCD',
                'name' => __('East Caribbean Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Eastern Caribbean States', Plugin::PLUGIN_SLUG),
            ],
            'XOF' => [
                'flag' => 'XOF',
                'name' => __('CFA Franc BCEAO', Plugin::PLUGIN_SLUG),
                'region' => __('West African States', Plugin::PLUGIN_SLUG),
            ],
            'XDR' => [
                'flag' => 'XDR',
                'name' => __('Special Drawing Rights', Plugin::PLUGIN_SLUG),
                'region' => __('IMF', Plugin::PLUGIN_SLUG),
            ],
            'LD' => [
                'flag' => 'LD',
                'name' => __('Linden Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Second Life', Plugin::PLUGIN_SLUG),
            ],
            'XPF' => [
                'flag' => 'XX',
                'name' => __('CFP Franc', Plugin::PLUGIN_SLUG),
                'region' => __('IEOM', Plugin::PLUGIN_SLUG),
            ],
            'YER' => [
                'flag' => 'YE',
                'name' => __('Yemeni Rial', Plugin::PLUGIN_SLUG),
                'region' => __('Yemen', Plugin::PLUGIN_SLUG),
            ],
            'ZAR' => [
                'flag' => 'ZA',
                'name' => __('South African Rand', Plugin::PLUGIN_SLUG),
                'region' => __('South Africa', Plugin::PLUGIN_SLUG),
            ],
            'ZMW' => [
                'flag' => 'ZM',
                'name' => __('Zambian Kwacha', Plugin::PLUGIN_SLUG),
                'region' => __('Zambia', Plugin::PLUGIN_SLUG),
            ],
            'ZWL' => [
                'flag' => 'ZW',
                'name' => __('Zimbabwean Dollar', Plugin::PLUGIN_SLUG),
                'region' => __('Zimbabwe', Plugin::PLUGIN_SLUG),
            ],
            'WAUA' => [
                'flag' => 'WAUA',
                'name' => __('W-African Unit of Account', Plugin::PLUGIN_SLUG),
                'region' => __('ECOWAS', Plugin::PLUGIN_SLUG),
            ],
            'BNB' => [
                'flag' => 'BNB',
                'name' => __('Binance Coin', Plugin::PLUGIN_SLUG),
                'region' => __('Crypto', Plugin::PLUGIN_SLUG),
            ],
        ];
    }
}
