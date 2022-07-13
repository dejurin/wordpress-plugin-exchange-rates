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
                'name' => esc_html__('UAE Dirham', 'exchange-rates'),
                'region' => esc_html__('UAE', 'exchange-rates'),
            ],
            'AFN' => [
                'flag' => 'AF',
                'name' => esc_html__('Afghan Afghani', 'exchange-rates'),
                'region' => esc_html__('Afghanistan', 'exchange-rates'),
            ],
            'ALL' => [
                'flag' => 'AL',
                'name' => esc_html__('Albanian Lek', 'exchange-rates'),
                'region' => esc_html__('Albania', 'exchange-rates'),
            ],
            'AMD' => [
                'flag' => 'AM',
                'name' => esc_html__('Armenian Dram', 'exchange-rates'),
                'region' => esc_html__('Armenia', 'exchange-rates'),
            ],
            'ANG' => [
                'flag' => 'AN',
                'name' => esc_html__('Guilder', 'exchange-rates'),
                'region' => esc_html__('Netherlands Antilles', 'exchange-rates'),
            ],
            'AOA' => [
                'flag' => 'AO',
                'name' => esc_html__('Angolan Kwanza', 'exchange-rates'),
                'region' => esc_html__('Angola', 'exchange-rates'),
            ],
            'ARS' => [
                'flag' => 'AR',
                'name' => esc_html__('Argentine Peso', 'exchange-rates'),
                'region' => esc_html__('Argentina', 'exchange-rates'),
            ],
            'AUD' => [
                'flag' => 'AU',
                'name' => esc_html__('Australian Dollar', 'exchange-rates'),
                'region' => esc_html__('Australia', 'exchange-rates'),
            ],
            'AWG' => [
                'flag' => 'AW',
                'name' => esc_html__('Aruban Florin', 'exchange-rates'),
                'region' => esc_html__('Aruba', 'exchange-rates'),
            ],
            'AZN' => [
                'flag' => 'AZ',
                'name' => esc_html__('Azerbaijanian Manat', 'exchange-rates'),
                'region' => esc_html__('Azerbaijan', 'exchange-rates'),
            ],
            'BAM' => [
                'flag' => 'BA',
                'name' => esc_html__('Convertible Mark', 'exchange-rates'),
                'region' => esc_html__('Bosnia & Herzegovina', 'exchange-rates'),
            ],
            'BBD' => [
                'flag' => 'BB',
                'name' => esc_html__('Barbados Dollar', 'exchange-rates'),
                'region' => esc_html__('Barbados', 'exchange-rates'),
            ],
            'BDT' => [
                'flag' => 'BD',
                'name' => esc_html__('Bangladeshi Taka', 'exchange-rates'),
                'region' => esc_html__('Bangladesh', 'exchange-rates'),
            ],
            'BGN' => [
                'flag' => 'BG',
                'name' => esc_html__('Bulgarian Lev', 'exchange-rates'),
                'region' => esc_html__('Bulgaria', 'exchange-rates'),
            ],
            'BHD' => [
                'flag' => 'BH',
                'name' => esc_html__('Bahraini Dinar', 'exchange-rates'),
                'region' => esc_html__('Bahrain', 'exchange-rates'),
            ],
            'BIF' => [
                'flag' => 'BI',
                'name' => esc_html__('Burundi Franc', 'exchange-rates'),
                'region' => esc_html__('Burundi', 'exchange-rates'),
            ],
            'BMD' => [
                'flag' => 'BM',
                'name' => esc_html__('Bermudian Dollar', 'exchange-rates'),
                'region' => esc_html__('Bermuda', 'exchange-rates'),
            ],
            'BND' => [
                'flag' => 'BN',
                'name' => esc_html__('Brunei Dollar', 'exchange-rates'),
                'region' => esc_html__('Brunei Darussalam', 'exchange-rates'),
            ],
            'BOB' => [
                'flag' => 'BO',
                'name' => esc_html__('Boliviano', 'exchange-rates'),
                'region' => esc_html__('Bolivia', 'exchange-rates'),
            ],
            'BRL' => [
                'flag' => 'BR',
                'name' => esc_html__('Brazilian Real', 'exchange-rates'),
                'region' => esc_html__('Brazil', 'exchange-rates'),
            ],
            'BSD' => [
                'flag' => 'BS',
                'name' => esc_html__('Bahamian Dollar', 'exchange-rates'),
                'region' => esc_html__('The Bahamas', 'exchange-rates'),
            ],
            'BTC' => [
                'flag' => 'BTC',
                'name' => esc_html__('Bitcoin', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'ETH' => [
                'flag' => 'ETH',
                'name' => esc_html__('Ethereum', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'LTC' => [
                'flag' => 'LTC',
                'name' => esc_html__('Litecoin', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'DOGE' => [
                'flag' => 'DOGE',
                'name' => esc_html__('Doge', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'DASH' => [
                'flag' => 'DASH',
                'name' => esc_html__('Dash', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'NXT' => [
                'flag' => 'NXT',
                'name' => esc_html__('NXT', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'STR' => [
                'flag' => 'STR',
                'name' => esc_html__('STR', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'XRP' => [
                'flag' => 'XRP',
                'name' => esc_html__('Ripple', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'XMR' => [
                'flag' => 'XMR',
                'name' => esc_html__('Monero', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'NMC' => [
                'flag' => 'NMC',
                'name' => esc_html__('NMC', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'PPC' => [
                'flag' => 'PPC',
                'name' => esc_html__('PPC', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'NVC' => [
                'flag' => 'NVC',
                'name' => esc_html__('NVC', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'XPM' => [
                'flag' => 'XPM',
                'name' => esc_html__('XPM', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'EAC' => [
                'flag' => 'EAC',
                'name' => esc_html__('EAC', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'VTC' => [
                'flag' => 'VTC',
                'name' => esc_html__('VTC', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'EMC' => [
                'flag' => 'EMC',
                'name' => esc_html__('EMC', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'FCT' => [
                'flag' => 'FCT',
                'name' => esc_html__('FCT', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'BTN' => [
                'flag' => 'BT',
                'name' => esc_html__('Bhutanese Ngultrum', 'exchange-rates'),
                'region' => esc_html__('Bhutan', 'exchange-rates'),
            ],
            'BWP' => [
                'flag' => 'BW',
                'name' => esc_html__('Botswana Pula', 'exchange-rates'),
                'region' => esc_html__('Botswana', 'exchange-rates'),
            ],
            'BYN' => [
                'flag' => 'BY',
                'name' => esc_html__('Belarusian ruble', 'exchange-rates'),
                'region' => esc_html__('Belarus', 'exchange-rates'),
            ],
            'BTS' => [
                'flag' => 'BTS',
                'name' => esc_html__('BitShares', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
            'BYR' => [
                'flag' => 'BY',
                'name' => esc_html__('Belarussian Ruble', 'exchange-rates'),
                'region' => esc_html__('Belarus', 'exchange-rates'),
            ],
            'BZD' => [
                'flag' => 'BZ',
                'name' => esc_html__('Belize Dollar', 'exchange-rates'),
                'region' => esc_html__('Belize', 'exchange-rates'),
            ],
            'CAD' => [
                'flag' => 'CA',
                'name' => esc_html__('Canadian Dollar', 'exchange-rates'),
                'region' => esc_html__('Canada', 'exchange-rates'),
            ],
            'CDF' => [
                'flag' => 'CD',
                'name' => esc_html__('Congolese Franc', 'exchange-rates'),
                'region' => esc_html__('DR Congo', 'exchange-rates'),
            ],
            'CHF' => [
                'flag' => 'CH',
                'name' => esc_html__('Swiss Franc', 'exchange-rates'),
                'region' => esc_html__('Switzerland', 'exchange-rates'),
            ],
            'CLP' => [
                'flag' => 'CL',
                'name' => esc_html__('Chilean Peso', 'exchange-rates'),
                'region' => esc_html__('Chile', 'exchange-rates'),
            ],
            'CLF' => [
                'flag' => 'CL',
                'name' => esc_html__('Chilean Unit of Account', 'exchange-rates'),
                'region' => esc_html__('Chile', 'exchange-rates'),
            ],
            'CNY' => [
                'flag' => 'CN',
                'name' => esc_html__('Renminbi', 'exchange-rates'),
                'region' => esc_html__('China', 'exchange-rates'),
            ],
            'CNH' => [
                'flag' => 'CN',
                'name' => esc_html__('Chinese Yuan (offshore)', 'exchange-rates'),
                'region' => esc_html__('China', 'exchange-rates'),
            ],
            'COP' => [
                'flag' => 'CO',
                'name' => esc_html__('Colombian Peso', 'exchange-rates'),
                'region' => esc_html__('Colombia', 'exchange-rates'),
            ],
            'CRC' => [
                'flag' => 'CR',
                'name' => esc_html__('Costa Rican Colon', 'exchange-rates'),
                'region' => esc_html__('Costa Rica', 'exchange-rates'),
            ],
            'CUP' => [
                'flag' => 'CU',
                'name' => esc_html__('Cuban Peso', 'exchange-rates'),
                'region' => esc_html__('Cuba', 'exchange-rates'),
            ],
            'CUC' => [
                'flag' => 'CU',
                'name' => esc_html__('Convertible peso', 'exchange-rates'),
                'region' => esc_html__('Cuba', 'exchange-rates'),
            ],
            'CVE' => [
                'flag' => 'CV',
                'name' => esc_html__('Cabo Verde Escudo', 'exchange-rates'),
                'region' => esc_html__('Cape Verde', 'exchange-rates'),
            ],
            'CZK' => [
                'flag' => 'CZ',
                'name' => esc_html__('Czech Koruna', 'exchange-rates'),
                'region' => esc_html__('Czech Republic', 'exchange-rates'),
            ],
            'DJF' => [
                'flag' => 'DJ',
                'name' => esc_html__('Djibouti Franc', 'exchange-rates'),
                'region' => esc_html__('Djibouti', 'exchange-rates'),
            ],
            'DKK' => [
                'flag' => 'DK',
                'name' => esc_html__('Danish Krone', 'exchange-rates'),
                'region' => esc_html__('Denmark', 'exchange-rates'),
            ],
            'DOP' => [
                'flag' => 'DO',
                'name' => esc_html__('Dominican Peso', 'exchange-rates'),
                'region' => esc_html__('Dominican Republic', 'exchange-rates'),
            ],
            'DZD' => [
                'flag' => 'DZ',
                'name' => esc_html__('Algerian Dinar', 'exchange-rates'),
                'region' => esc_html__('Algeria', 'exchange-rates'),
            ],
            'EGP' => [
                'flag' => 'EG',
                'name' => esc_html__('Egyptian Pound', 'exchange-rates'),
                'region' => esc_html__('Egypt', 'exchange-rates'),
            ],
            'ERN' => [
                'flag' => 'ER',
                'name' => esc_html__('Eritrean Nakfa', 'exchange-rates'),
                'region' => esc_html__('Eritrea', 'exchange-rates'),
            ],
            'ETB' => [
                'flag' => 'ET',
                'name' => esc_html__('Ethiopian Birr', 'exchange-rates'),
                'region' => esc_html__('Ethiopia', 'exchange-rates'),
            ],
            'EUR' => [
                'flag' => 'EU',
                'name' => esc_html__('Euro', 'exchange-rates'),
                'region' => esc_html__('European Union', 'exchange-rates'),
            ],
            'FJD' => [
                'flag' => 'FJ',
                'name' => esc_html__('Fiji Dollar', 'exchange-rates'),
                'region' => esc_html__('Fiji', 'exchange-rates'),
            ],
            'FKP' => [
                'flag' => 'FK',
                'name' => esc_html__('Falkland Islands Pound', 'exchange-rates'),
                'region' => esc_html__('Falkland Islands', 'exchange-rates'),
            ],
            'GBP' => [
                'flag' => 'GB',
                'name' => esc_html__('British Pound', 'exchange-rates'),
                'region' => esc_html__('United Kingdom', 'exchange-rates'),
            ],
            'IMP' => [
                'flag' => 'IM',
                'name' => esc_html__('Manx pound', 'exchange-rates'),
                'region' => esc_html__('Isle of Man', 'exchange-rates'),
            ],
            'GEL' => [
                'flag' => 'GE',
                'name' => esc_html__('Georgian Lari', 'exchange-rates'),
                'region' => esc_html__('Georgia', 'exchange-rates'),
            ],
            'GGP' => [
                'flag' => 'GG',
                'name' => esc_html__('Guernsey Pound', 'exchange-rates'),
                'region' => esc_html__('Guernsey', 'exchange-rates'),
            ],
            'GHS' => [
                'flag' => 'GH',
                'name' => esc_html__('Ghana Cedi', 'exchange-rates'),
                'region' => esc_html__('Ghana', 'exchange-rates'),
            ],
            'GIP' => [
                'flag' => 'GI',
                'name' => esc_html__('Gibraltar Pound', 'exchange-rates'),
                'region' => esc_html__('Gibraltar', 'exchange-rates'),
            ],
            'GMD' => [
                'flag' => 'GM',
                'name' => esc_html__('Gambian Dalasi', 'exchange-rates'),
                'region' => esc_html__('The Gambia', 'exchange-rates'),
            ],
            'GNF' => [
                'flag' => 'GN',
                'name' => esc_html__('Guinea Franc', 'exchange-rates'),
                'region' => esc_html__('Guinea', 'exchange-rates'),
            ],
            'GTQ' => [
                'flag' => 'GT',
                'name' => esc_html__('Guatemalan Quetzal', 'exchange-rates'),
                'region' => esc_html__('Guatemala', 'exchange-rates'),
            ],
            'GYD' => [
                'flag' => 'GY',
                'name' => esc_html__('Guyana Dollar', 'exchange-rates'),
                'region' => esc_html__('Guyana', 'exchange-rates'),
            ],
            'HKD' => [
                'flag' => 'HK',
                'name' => esc_html__('Hong Kong Dollar', 'exchange-rates'),
                'region' => esc_html__('Hong Kong', 'exchange-rates'),
            ],
            'HNL' => [
                'flag' => 'HN',
                'name' => esc_html__('Honduran Lempira', 'exchange-rates'),
                'region' => esc_html__('Honduras', 'exchange-rates'),
            ],
            'HRK' => [
                'flag' => 'HR',
                'name' => esc_html__('Croatian Kuna', 'exchange-rates'),
                'region' => esc_html__('Croatia', 'exchange-rates'),
            ],
            'HTG' => [
                'flag' => 'HT',
                'name' => esc_html__('Haitian Gourde', 'exchange-rates'),
                'region' => esc_html__('Haiti', 'exchange-rates'),
            ],
            'HUF' => [
                'flag' => 'HU',
                'name' => esc_html__('Hungarian Forint', 'exchange-rates'),
                'region' => esc_html__('Hungary', 'exchange-rates'),
            ],
            'IDR' => [
                'flag' => 'ID',
                'name' => esc_html__('Indonesian Rupiah', 'exchange-rates'),
                'region' => esc_html__('Indonesia', 'exchange-rates'),
            ],
            'ILS' => [
                'flag' => 'IL',
                'name' => esc_html__('Israeli Shekel', 'exchange-rates'),
                'region' => esc_html__('Israel', 'exchange-rates'),
            ],
            'INR' => [
                'flag' => 'IN',
                'name' => esc_html__('Indian Rupee', 'exchange-rates'),
                'region' => esc_html__('India', 'exchange-rates'),
            ],
            'IQD' => [
                'flag' => 'IQ',
                'name' => esc_html__('Iraqi Dinar', 'exchange-rates'),
                'region' => esc_html__('Iraq', 'exchange-rates'),
            ],
            'IRR' => [
                'flag' => 'IR',
                'name' => esc_html__('Iranian Rial', 'exchange-rates'),
                'region' => esc_html__('Iran', 'exchange-rates'),
            ],
            'ISK' => [
                'flag' => 'IS',
                'name' => esc_html__('Iceland Krona', 'exchange-rates'),
                'region' => esc_html__('Iceland', 'exchange-rates'),
            ],
            'JEP' => [
                'flag' => 'JE',
                'name' => esc_html__('Jersey Pound', 'exchange-rates'),
                'region' => esc_html__('Jersey', 'exchange-rates'),
            ],
            'JMD' => [
                'flag' => 'JM',
                'name' => esc_html__('Jamaican Dollar', 'exchange-rates'),
                'region' => esc_html__('Jamaica', 'exchange-rates'),
            ],
            'JOD' => [
                'flag' => 'JO',
                'name' => esc_html__('Jordanian Dinar', 'exchange-rates'),
                'region' => esc_html__('Jordan', 'exchange-rates'),
            ],
            'JPY' => [
                'flag' => 'JP',
                'name' => esc_html__('Japanese Yen', 'exchange-rates'),
                'region' => esc_html__('Japan', 'exchange-rates'),
            ],
            'KES' => [
                'flag' => 'KE',
                'name' => esc_html__('Kenyan Shilling', 'exchange-rates'),
                'region' => esc_html__('Kenya', 'exchange-rates'),
            ],
            'KGS' => [
                'flag' => 'KG',
                'name' => esc_html__('Kyrgyzstani Som', 'exchange-rates'),
                'region' => esc_html__('Kyrgyzstan', 'exchange-rates'),
            ],
            'KHR' => [
                'flag' => 'KH',
                'name' => esc_html__('Cambodian Riel', 'exchange-rates'),
                'region' => esc_html__('Cambodia', 'exchange-rates'),
            ],
            'KMF' => [
                'flag' => 'KM',
                'name' => esc_html__('Comoro Franc', 'exchange-rates'),
                'region' => esc_html__('The Comoros', 'exchange-rates'),
            ],
            'KPW' => [
                'flag' => 'KP',
                'name' => esc_html__('North Korean Won', 'exchange-rates'),
                'region' => esc_html__('North Korea', 'exchange-rates'),
            ],
            'KRW' => [
                'flag' => 'KR',
                'name' => esc_html__('South Korean Won', 'exchange-rates'),
                'region' => esc_html__('South Korea', 'exchange-rates'),
            ],
            'KWD' => [
                'flag' => 'KW',
                'name' => esc_html__('Kuwaiti Dinar', 'exchange-rates'),
                'region' => esc_html__('Kuwait', 'exchange-rates'),
            ],
            'KYD' => [
                'flag' => 'KY',
                'name' => esc_html__('Cayman Islands Dollar', 'exchange-rates'),
                'region' => esc_html__('The Cayman Islands', 'exchange-rates'),
            ],
            'KZT' => [
                'flag' => 'KZ',
                'name' => esc_html__('Kazakhstani Tenge', 'exchange-rates'),
                'region' => esc_html__('Kazakhstan', 'exchange-rates'),
            ],
            'LAK' => [
                'flag' => 'LA',
                'name' => esc_html__('Lao Kip', 'exchange-rates'),
                'region' => esc_html__('Laos', 'exchange-rates'),
            ],
            'LBP' => [
                'flag' => 'LB',
                'name' => esc_html__('Lebanese Pound', 'exchange-rates'),
                'region' => esc_html__('Lebanon', 'exchange-rates'),
            ],
            'LKR' => [
                'flag' => 'LK',
                'name' => esc_html__('Sri Lanka Rupee', 'exchange-rates'),
                'region' => esc_html__('Sri Lanka', 'exchange-rates'),
            ],
            'LRD' => [
                'flag' => 'LR',
                'name' => esc_html__('Liberian Dollar', 'exchange-rates'),
                'region' => esc_html__('Liberia', 'exchange-rates'),
            ],
            'LSL' => [
                'flag' => 'LS',
                'name' => esc_html__('Lesotho Loti', 'exchange-rates'),
                'region' => esc_html__('Lesotho', 'exchange-rates'),
            ],
            'LYD' => [
                'flag' => 'LY',
                'name' => esc_html__('Libyan Dinar', 'exchange-rates'),
                'region' => esc_html__('Libya', 'exchange-rates'),
            ],
            'MAD' => [
                'flag' => 'MA',
                'name' => esc_html__('Moroccan Dirham', 'exchange-rates'),
                'region' => esc_html__('Morocco', 'exchange-rates'),
            ],
            'MDL' => [
                'flag' => 'MD',
                'name' => esc_html__('Moldovan Leu', 'exchange-rates'),
                'region' => esc_html__('Moldova', 'exchange-rates'),
            ],
            'MGA' => [
                'flag' => 'MG',
                'name' => esc_html__('Malagasy Ariary', 'exchange-rates'),
                'region' => esc_html__('Madagascar', 'exchange-rates'),
            ],
            'MKD' => [
                'flag' => 'MK',
                'name' => esc_html__('Macedonian Denar', 'exchange-rates'),
                'region' => esc_html__('Macedonia', 'exchange-rates'),
            ],
            'MMK' => [
                'flag' => 'MM',
                'name' => esc_html__('Myanmar Kyat', 'exchange-rates'),
                'region' => esc_html__('Myanmar', 'exchange-rates'),
            ],
            'MNT' => [
                'flag' => 'MN',
                'name' => esc_html__('Mongolian Tugrik', 'exchange-rates'),
                'region' => esc_html__('Mongolia', 'exchange-rates'),
            ],
            'MOP' => [
                'flag' => 'MO',
                'name' => esc_html__('Macanese Pataca', 'exchange-rates'),
                'region' => esc_html__('Macao', 'exchange-rates'),
            ],
            'MRO' => [
                'flag' => 'MR',
                'name' => esc_html__('Mauritanian Ouguiya', 'exchange-rates'),
                'region' => esc_html__('Mauritania', 'exchange-rates'),
            ],
            'MRU' => [
                'flag' => 'MR',
                'name' => esc_html__('Mauritanian Ouguiya', 'exchange-rates'),
                'region' => esc_html__('Mauritania', 'exchange-rates'),
            ],
            'MUR' => [
                'flag' => 'MU',
                'name' => esc_html__('Mauritius Rupee', 'exchange-rates'),
                'region' => esc_html__('Mauritius', 'exchange-rates'),
            ],
            'MVR' => [
                'flag' => 'MV',
                'name' => esc_html__('Maldivian Rufiyaa', 'exchange-rates'),
                'region' => esc_html__('Maldives', 'exchange-rates'),
            ],
            'MWK' => [
                'flag' => 'MW',
                'name' => esc_html__('Malawian Kwacha', 'exchange-rates'),
                'region' => esc_html__('Malawi', 'exchange-rates'),
            ],
            'MXN' => [
                'flag' => 'MX',
                'name' => esc_html__('Mexican Peso', 'exchange-rates'),
                'region' => esc_html__('Mexico', 'exchange-rates'),
            ],
            'MYR' => [
                'flag' => 'MY',
                'name' => esc_html__('Malaysian Ringgit', 'exchange-rates'),
                'region' => esc_html__('Malaysia', 'exchange-rates'),
            ],
            'MZN' => [
                'flag' => 'MZ',
                'name' => esc_html__('Mozambique Metical', 'exchange-rates'),
                'region' => esc_html__('Mozambique', 'exchange-rates'),
            ],
            'NAD' => [
                'flag' => 'NA',
                'name' => esc_html__('Namibia Dollar', 'exchange-rates'),
                'region' => esc_html__('Namibia', 'exchange-rates'),
            ],
            'NGN' => [
                'flag' => 'NG',
                'name' => esc_html__('Nigerian Naira', 'exchange-rates'),
                'region' => esc_html__('Nigeria', 'exchange-rates'),
            ],
            'NIO' => [
                'flag' => 'NI',
                'name' => esc_html__('Cordoba Oro', 'exchange-rates'),
                'region' => esc_html__('Nicaragua', 'exchange-rates'),
            ],
            'NOK' => [
                'flag' => 'NO',
                'name' => esc_html__('Norwegian Krone', 'exchange-rates'),
                'region' => esc_html__('Norway', 'exchange-rates'),
            ],
            'NPR' => [
                'flag' => 'NP',
                'name' => esc_html__('Nepalese Rupee', 'exchange-rates'),
                'region' => esc_html__('Nepal', 'exchange-rates'),
            ],
            'NZD' => [
                'flag' => 'NZ',
                'name' => esc_html__('New Zealand Dollar', 'exchange-rates'),
                'region' => esc_html__('New Zealand', 'exchange-rates'),
            ],
            'OMR' => [
                'flag' => 'OM',
                'name' => esc_html__('Rial Omani', 'exchange-rates'),
                'region' => esc_html__('Oman', 'exchange-rates'),
            ],
            'PAB' => [
                'flag' => 'PA',
                'name' => esc_html__('Panamanian Balboa', 'exchange-rates'),
                'region' => esc_html__('Panama', 'exchange-rates'),
            ],
            'PEN' => [
                'flag' => 'PE',
                'name' => esc_html__('Peruvian Sol', 'exchange-rates'),
                'region' => esc_html__('Peru', 'exchange-rates'),
            ],
            'PGK' => [
                'flag' => 'PG',
                'name' => esc_html__('Papua New Guinean Kina', 'exchange-rates'),
                'region' => esc_html__('Papua New Guinea', 'exchange-rates'),
            ],
            'PHP' => [
                'flag' => 'PH',
                'name' => esc_html__('Philippine Peso', 'exchange-rates'),
                'region' => esc_html__('Philippines', 'exchange-rates'),
            ],
            'PKR' => [
                'flag' => 'PK',
                'name' => esc_html__('Pakistani Rupee', 'exchange-rates'),
                'region' => esc_html__('Pakistan', 'exchange-rates'),
            ],
            'PLN' => [
                'flag' => 'PL',
                'name' => esc_html__('Polish Zloty', 'exchange-rates'),
                'region' => esc_html__('Poland', 'exchange-rates'),
            ],
            'PYG' => [
                'flag' => 'PY',
                'name' => esc_html__('Paraguayan Guarani', 'exchange-rates'),
                'region' => esc_html__('Paraguay', 'exchange-rates'),
            ],
            'QAR' => [
                'flag' => 'QA',
                'name' => esc_html__('Qatari Rial', 'exchange-rates'),
                'region' => esc_html__('Qatar', 'exchange-rates'),
            ],
            'RON' => [
                'flag' => 'RO',
                'name' => esc_html__('Romanian Leu', 'exchange-rates'),
                'region' => esc_html__('Romania', 'exchange-rates'),
            ],
            'RSD' => [
                'flag' => 'RS',
                'name' => esc_html__('Serbian Dinar', 'exchange-rates'),
                'region' => esc_html__('Serbia', 'exchange-rates'),
            ],
            'RUB' => [
                'flag' => 'RU',
                'name' => esc_html__('Russian Ruble', 'exchange-rates'),
                'region' => esc_html__('Russia', 'exchange-rates'),
            ],
            'PRB' => [
                'flag' => 'XX',
                'name' => esc_html__('Transnistrian ruble', 'exchange-rates'),
                'region' => esc_html__('Transnistria', 'exchange-rates'),
            ],
            'RUR' => [
                'flag' => 'XX',
                'name' => esc_html__('USSR Ruble', 'exchange-rates'),
                'region' => esc_html__('USSR', 'exchange-rates'),
            ],
            'RWF' => [
                'flag' => 'RW',
                'name' => esc_html__('Rwanda Franc', 'exchange-rates'),
                'region' => esc_html__('Rwanda', 'exchange-rates'),
            ],
            'SAR' => [
                'flag' => 'SA',
                'name' => esc_html__('Saudi Riyal', 'exchange-rates'),
                'region' => esc_html__('Saudi Arabia', 'exchange-rates'),
            ],
            'SBD' => [
                'flag' => 'SB',
                'name' => esc_html__('Solomon Islands Dollar', 'exchange-rates'),
                'region' => esc_html__('Solomon Islands', 'exchange-rates'),
            ],
            'SCR' => [
                'flag' => 'SC',
                'name' => esc_html__('Seychelles Rupee', 'exchange-rates'),
                'region' => esc_html__('Seychelles', 'exchange-rates'),
            ],
            'SDG' => [
                'flag' => 'SD',
                'name' => esc_html__('Sudanese Pound', 'exchange-rates'),
                'region' => esc_html__('The Sudan', 'exchange-rates'),
            ],
            'SEK' => [
                'flag' => 'SE',
                'name' => esc_html__('Swedish Krona', 'exchange-rates'),
                'region' => esc_html__('Sweden', 'exchange-rates'),
            ],
            'SGD' => [
                'flag' => 'SG',
                'name' => esc_html__('Singapore Dollar', 'exchange-rates'),
                'region' => esc_html__('Singapore', 'exchange-rates'),
            ],
            'SHP' => [
                'flag' => 'SH',
                'name' => esc_html__('Saint Helena Pound', 'exchange-rates'),
                'region' => esc_html__('Saint Helena', 'exchange-rates'),
            ],
            'SLL' => [
                'flag' => 'SL',
                'name' => esc_html__('Sierra Leonean Leone', 'exchange-rates'),
                'region' => esc_html__('Sierra Leone', 'exchange-rates'),
            ],
            'SOS' => [
                'flag' => 'SO',
                'name' => esc_html__('Somali Shilling', 'exchange-rates'),
                'region' => esc_html__('Somalia', 'exchange-rates'),
            ],
            'SRD' => [
                'flag' => 'SR',
                'name' => esc_html__('Surinam Dollar', 'exchange-rates'),
                'region' => esc_html__('Suriname', 'exchange-rates'),
            ],
            'STD' => [
                'flag' => 'ST',
                'name' => esc_html__('Sao Tomean Dobra', 'exchange-rates'),
                'region' => esc_html__('Sao Tome And Principe', 'exchange-rates'),
            ],
            'STN' => [
                'flag' => 'ST',
                'name' => esc_html__('Sao Tomean Dobra', 'exchange-rates'),
                'region' => esc_html__('Sao Tome And Principe', 'exchange-rates'),
            ],
            'SVC' => [
                'flag' => 'SV',
                'name' => esc_html__('El Salvador Colon', 'exchange-rates'),
                'region' => esc_html__('El Salvador', 'exchange-rates'),
            ],
            'SYP' => [
                'flag' => 'SY',
                'name' => esc_html__('Syrian Pound', 'exchange-rates'),
                'region' => esc_html__('Syrian Arab Republic', 'exchange-rates'),
            ],
            'SSP' => [
                'flag' => 'SS',
                'name' => esc_html__('South Sudanese Pound', 'exchange-rates'),
                'region' => esc_html__('South Sudan', 'exchange-rates'),
            ],
            'SZL' => [
                'flag' => 'SZ',
                'name' => esc_html__('Swazi Lilangeni', 'exchange-rates'),
                'region' => esc_html__('Swaziland', 'exchange-rates'),
            ],
            'THB' => [
                'flag' => 'TH',
                'name' => esc_html__('Thai Baht', 'exchange-rates'),
                'region' => esc_html__('Thailand', 'exchange-rates'),
            ],
            'TJS' => [
                'flag' => 'TJ',
                'name' => esc_html__('Tajikistani Somoni', 'exchange-rates'),
                'region' => esc_html__('Tajikistan', 'exchange-rates'),
            ],
            'TMT' => [
                'flag' => 'TM',
                'name' => esc_html__('Turkmenistan New Manat', 'exchange-rates'),
                'region' => esc_html__('Turkmenistan', 'exchange-rates'),
            ],
            'TND' => [
                'flag' => 'TN',
                'name' => esc_html__('Tunisian Dinar', 'exchange-rates'),
                'region' => esc_html__('Tunisia', 'exchange-rates'),
            ],
            'TOP' => [
                'flag' => 'TO',
                'name' => esc_html__('Tongan Paanga', 'exchange-rates'),
                'region' => esc_html__('Tonga', 'exchange-rates'),
            ],
            'TRY' => [
                'flag' => 'TR',
                'name' => esc_html__('Turkish Lira', 'exchange-rates'),
                'region' => esc_html__('Turkey', 'exchange-rates'),
            ],
            'TTD' => [
                'flag' => 'TT',
                'name' => esc_html__('Trinidad and Tobago Dollar', 'exchange-rates'),
                'region' => esc_html__('Trinidad And Tobago', 'exchange-rates'),
            ],
            'TWD' => [
                'flag' => 'TW',
                'name' => esc_html__('New Taiwan Dollar', 'exchange-rates'),
                'region' => esc_html__('Taiwan', 'exchange-rates'),
            ],
            'TZS' => [
                'flag' => 'TZ',
                'name' => esc_html__('Tanzanian Shilling', 'exchange-rates'),
                'region' => esc_html__('Tanzania', 'exchange-rates'),
            ],
            'UAH' => [
                'flag' => 'UA',
                'name' => esc_html__('Ukrainian Hryvnia', 'exchange-rates'),
                'region' => esc_html__('Ukraine', 'exchange-rates'),
            ],
            'UGX' => [
                'flag' => 'UG',
                'name' => esc_html__('Uganda Shilling', 'exchange-rates'),
                'region' => esc_html__('Uganda', 'exchange-rates'),
            ],
            'USD' => [
                'flag' => 'US',
                'name' => esc_html__('US Dollar', 'exchange-rates'),
                'region' => esc_html__('United States', 'exchange-rates'),
            ],
            'UYU' => [
                'flag' => 'UY',
                'name' => esc_html__('Peso Uruguayo', 'exchange-rates'),
                'region' => esc_html__('Uruguay', 'exchange-rates'),
            ],
            'UZS' => [
                'flag' => 'UZ',
                'name' => esc_html__('Uzbekistan Sum', 'exchange-rates'),
                'region' => esc_html__('Uzbekistan', 'exchange-rates'),
            ],
            'VEF' => [
                'flag' => 'VE',
                'name' => esc_html__('Venezuelan Bolivar', 'exchange-rates'),
                'region' => esc_html__('Venezuela', 'exchange-rates'),
            ],
            'VES' => [
                'flag' => 'VE',
                'name' => esc_html__('Venezuelan Bolivar', 'exchange-rates'),
                'region' => esc_html__('Venezuela', 'exchange-rates'),
            ],
            'VEF_DIPRO' => [
                'flag' => 'VE',
                'name' => esc_html__('VEF DIPRO', 'exchange-rates'),
                'region' => esc_html__('Venezuela', 'exchange-rates'),
            ],
            'VEF_DICOM' => [
                'flag' => 'VE',
                'name' => esc_html__('VEF DICOM', 'exchange-rates'),
                'region' => esc_html__('Venezuela', 'exchange-rates'),
            ],
            'VEF_BLKMKT' => [
                'flag' => 'VE',
                'name' => esc_html__('VEF BLKMKT', 'exchange-rates'),
                'region' => esc_html__('Venezuela', 'exchange-rates'),
            ],
            'VND' => [
                'flag' => 'VN',
                'name' => esc_html__('Vietnamese Dong', 'exchange-rates'),
                'region' => esc_html__('Vietnam', 'exchange-rates'),
            ],
            'VUV' => [
                'flag' => 'VU',
                'name' => esc_html__('Vanuatu Vatu', 'exchange-rates'),
                'region' => esc_html__('Vanuatu', 'exchange-rates'),
            ],
            'WST' => [
                'flag' => 'WS',
                'name' => esc_html__('Samoan Tala', 'exchange-rates'),
                'region' => esc_html__('Samoa', 'exchange-rates'),
            ],
            'XAF' => [
                'flag' => 'CF',
                'name' => esc_html__('CFA Franc BEAC', 'exchange-rates'),
                'region' => esc_html__('Central African States', 'exchange-rates'),
            ],
            'XAU' => [
                'flag' => 'XAU',
                'name' => esc_html__('Gold (ounce)', 'exchange-rates'),
                'region' => esc_html__('&ndash;', 'exchange-rates'),
            ],
            'XAG' => [
                'flag' => 'XAG',
                'name' => esc_html__('Silver (ounce)', 'exchange-rates'),
                'region' => esc_html__('&ndash;', 'exchange-rates'),
            ],
            'XPD' => [
                'flag' => 'XPD',
                'name' => esc_html__('Palladium (ounce)', 'exchange-rates'),
                'region' => esc_html__('&ndash;', 'exchange-rates'),
            ],
            'XPT' => [
                'flag' => 'XPT',
                'name' => esc_html__('Platinum (ounce)', 'exchange-rates'),
                'region' => esc_html__('&ndash;', 'exchange-rates'),
            ],
            'XCD' => [
                'flag' => 'XCD',
                'name' => esc_html__('East Caribbean Dollar', 'exchange-rates'),
                'region' => esc_html__('Eastern Caribbean States', 'exchange-rates'),
            ],
            'XOF' => [
                'flag' => 'XOF',
                'name' => esc_html__('CFA Franc BCEAO', 'exchange-rates'),
                'region' => esc_html__('West African States', 'exchange-rates'),
            ],
            'XDR' => [
                'flag' => 'XDR',
                'name' => esc_html__('Special Drawing Rights', 'exchange-rates'),
                'region' => esc_html__('IMF', 'exchange-rates'),
            ],
            'LD' => [
                'flag' => 'LD',
                'name' => esc_html__('Linden Dollar', 'exchange-rates'),
                'region' => esc_html__('Second Life', 'exchange-rates'),
            ],
            'XPF' => [
                'flag' => 'XX',
                'name' => esc_html__('CFP Franc', 'exchange-rates'),
                'region' => esc_html__('IEOM', 'exchange-rates'),
            ],
            'YER' => [
                'flag' => 'YE',
                'name' => esc_html__('Yemeni Rial', 'exchange-rates'),
                'region' => esc_html__('Yemen', 'exchange-rates'),
            ],
            'ZAR' => [
                'flag' => 'ZA',
                'name' => esc_html__('South African Rand', 'exchange-rates'),
                'region' => esc_html__('South Africa', 'exchange-rates'),
            ],
            'ZMW' => [
                'flag' => 'ZM',
                'name' => esc_html__('Zambian Kwacha', 'exchange-rates'),
                'region' => esc_html__('Zambia', 'exchange-rates'),
            ],
            'ZWL' => [
                'flag' => 'ZW',
                'name' => esc_html__('Zimbabwean Dollar', 'exchange-rates'),
                'region' => esc_html__('Zimbabwe', 'exchange-rates'),
            ],
            'WAUA' => [
                'flag' => 'WAUA',
                'name' => esc_html__('W-African Unit of Account', 'exchange-rates'),
                'region' => esc_html__('ECOWAS', 'exchange-rates'),
            ],
            'BNB' => [
                'flag' => 'BNB',
                'name' => esc_html__('Binance Coin', 'exchange-rates'),
                'region' => esc_html__('Crypto', 'exchange-rates'),
            ],
        ];
    }
}
