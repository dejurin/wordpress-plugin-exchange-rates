<?php

namespace Dejurin\ExchangeRates\Models;

class Emoji
{
    public static function get_list()
    {
        $emoji_flags['EU'] = '1F4B6';
        $emoji_flags['GB'] = '1F4B7';
        $emoji_flags['US'] = '1F4B5';
        $emoji_flags['JP'] = '1F4B4';
        $emoji_flags['AD'] = '1F1E6-1F1E9';
        $emoji_flags['AE'] = '1F1E6-1F1EA';
        $emoji_flags['AF'] = '1F1E6-1F1EB';
        $emoji_flags['AG'] = '1F1E6-1F1EC';
        $emoji_flags['AI'] = '1F1E6-1F1EE';
        $emoji_flags['AL'] = '1F1E6-1F1F1';
        $emoji_flags['AM'] = '1F1E6-1F1F2';
        $emoji_flags['AO'] = '1F1E6-1F1F4';
        $emoji_flags['AQ'] = '1F1E6-1F1F6';
        $emoji_flags['AR'] = '1F1E6-1F1F7';
        $emoji_flags['AS'] = '1F1E6-1F1F8';
        $emoji_flags['AT'] = '1F1E6-1F1F9';
        $emoji_flags['AU'] = '1F1E6-1F1FA';
        $emoji_flags['AW'] = '1F1E6-1F1FC';
        $emoji_flags['AX'] = '1F1E6-1F1FD';
        $emoji_flags['AZ'] = '1F1E6-1F1FF';
        $emoji_flags['BA'] = '1F1E7-1F1E6';
        $emoji_flags['BB'] = '1F1E7-1F1E7';
        $emoji_flags['BD'] = '1F1E7-1F1E9';
        $emoji_flags['BE'] = '1F1E7-1F1EA';
        $emoji_flags['BF'] = '1F1E7-1F1EB';
        $emoji_flags['BG'] = '1F1E7-1F1EC';
        $emoji_flags['BH'] = '1F1E7-1F1ED';
        $emoji_flags['BI'] = '1F1E7-1F1EE';
        $emoji_flags['BJ'] = '1F1E7-1F1EF';
        $emoji_flags['BL'] = '1F1E7-1F1F1';
        $emoji_flags['BM'] = '1F1E7-1F1F2';
        $emoji_flags['BN'] = '1F1E7-1F1F3';
        $emoji_flags['BO'] = '1F1E7-1F1F4';
        $emoji_flags['BQ'] = '1F1E7-1F1F6';
        $emoji_flags['BR'] = '1F1E7-1F1F7';
        $emoji_flags['BS'] = '1F1E7-1F1F8';
        $emoji_flags['BT'] = '1F1E7-1F1F9';
        $emoji_flags['BV'] = '1F1E7-1F1FB';
        $emoji_flags['BW'] = '1F1E7-1F1FC';
        $emoji_flags['BY'] = '1F1E7-1F1FE';
        $emoji_flags['BZ'] = '1F1E7-1F1FF';
        $emoji_flags['CA'] = '1F1E8-1F1E6';
        $emoji_flags['CC'] = '1F1E8-1F1E8';
        $emoji_flags['CD'] = '1F1E8-1F1E9';
        $emoji_flags['CF'] = '1F1E8-1F1EB';
        $emoji_flags['CG'] = '1F1E8-1F1EC';
        $emoji_flags['CH'] = '1F1E8-1F1ED';
        $emoji_flags['CI'] = '1F1E8-1F1EE';
        $emoji_flags['CK'] = '1F1E8-1F1F0';
        $emoji_flags['CL'] = '1F1E8-1F1F1';
        $emoji_flags['CM'] = '1F1E8-1F1F2';
        $emoji_flags['CN'] = '1F1E8-1F1F3';
        $emoji_flags['CO'] = '1F1E8-1F1F4';
        $emoji_flags['CR'] = '1F1E8-1F1F7';
        $emoji_flags['CU'] = '1F1E8-1F1FA';
        $emoji_flags['CV'] = '1F1E8-1F1FB';
        $emoji_flags['CW'] = '1F1E8-1F1FC';
        $emoji_flags['CX'] = '1F1E8-1F1FD';
        $emoji_flags['CY'] = '1F1E8-1F1FE';
        $emoji_flags['CZ'] = '1F1E8-1F1FF';
        $emoji_flags['DE'] = '1F1E9-1F1EA';
        $emoji_flags['DG'] = '1F1E9-1F1EC';
        $emoji_flags['DJ'] = '1F1E9-1F1EF';
        $emoji_flags['DK'] = '1F1E9-1F1F0';
        $emoji_flags['DM'] = '1F1E9-1F1F2';
        $emoji_flags['DO'] = '1F1E9-1F1F4';
        $emoji_flags['DZ'] = '1F1E9-1F1FF';
        $emoji_flags['EC'] = '1F1EA-1F1E8';
        $emoji_flags['EE'] = '1F1EA-1F1EA';
        $emoji_flags['EG'] = '1F1EA-1F1EC';
        $emoji_flags['EH'] = '1F1EA-1F1ED';
        $emoji_flags['ER'] = '1F1EA-1F1F7';
        $emoji_flags['ES'] = '1F1EA-1F1F8';
        $emoji_flags['ET'] = '1F1EA-1F1F9';
        $emoji_flags['FI'] = '1F1EB-1F1EE';
        $emoji_flags['FJ'] = '1F1EB-1F1EF';
        $emoji_flags['FK'] = '1F1EB-1F1F0';
        $emoji_flags['FM'] = '1F1EB-1F1F2';
        $emoji_flags['FO'] = '1F1EB-1F1F4';
        $emoji_flags['FR'] = '1F1EB-1F1F7';
        $emoji_flags['GA'] = '1F1EC-1F1E6';
        $emoji_flags['GD'] = '1F1EC-1F1E9';
        $emoji_flags['GE'] = '1F1EC-1F1EA';
        $emoji_flags['GF'] = '1F1EC-1F1EB';
        $emoji_flags['GG'] = '1F1EC-1F1EC';
        $emoji_flags['GH'] = '1F1EC-1F1ED';
        $emoji_flags['GI'] = '1F1EC-1F1EE';
        $emoji_flags['GL'] = '1F1EC-1F1F1';
        $emoji_flags['GM'] = '1F1EC-1F1F2';
        $emoji_flags['GN'] = '1F1EC-1F1F3';
        $emoji_flags['GP'] = '1F1EC-1F1F5';
        $emoji_flags['GQ'] = '1F1EC-1F1F6';
        $emoji_flags['GR'] = '1F1EC-1F1F7';
        $emoji_flags['GS'] = '1F1EC-1F1F8';
        $emoji_flags['GT'] = '1F1EC-1F1F9';
        $emoji_flags['GU'] = '1F1EC-1F1FA';
        $emoji_flags['GW'] = '1F1EC-1F1FC';
        $emoji_flags['GY'] = '1F1EC-1F1FE';
        $emoji_flags['HK'] = '1F1ED-1F1F0';
        $emoji_flags['HM'] = '1F1ED-1F1F2';
        $emoji_flags['HN'] = '1F1ED-1F1F3';
        $emoji_flags['HR'] = '1F1ED-1F1F7';
        $emoji_flags['HT'] = '1F1ED-1F1F9';
        $emoji_flags['HU'] = '1F1ED-1F1FA';
        $emoji_flags['ID'] = '1F1EE-1F1E9';
        $emoji_flags['IE'] = '1F1EE-1F1EA';
        $emoji_flags['IL'] = '1F1EE-1F1F1';
        $emoji_flags['IM'] = '1F1EE-1F1F2';
        $emoji_flags['IN'] = '1F1EE-1F1F3';
        $emoji_flags['IO'] = '1F1EE-1F1F4';
        $emoji_flags['IQ'] = '1F1EE-1F1F6';
        $emoji_flags['IR'] = '1F1EE-1F1F7';
        $emoji_flags['IS'] = '1F1EE-1F1F8';
        $emoji_flags['IT'] = '1F1EE-1F1F9';
        $emoji_flags['JE'] = '1F1EF-1F1EA';
        $emoji_flags['JM'] = '1F1EF-1F1F2';
        $emoji_flags['JO'] = '1F1EF-1F1F4';
        $emoji_flags['KE'] = '1F1F0-1F1EA';
        $emoji_flags['KG'] = '1F1F0-1F1EC';
        $emoji_flags['KH'] = '1F1F0-1F1ED';
        $emoji_flags['KI'] = '1F1F0-1F1EE';
        $emoji_flags['KM'] = '1F1F0-1F1F2';
        $emoji_flags['KN'] = '1F1F0-1F1F3';
        $emoji_flags['KP'] = '1F1F0-1F1F5';
        $emoji_flags['KR'] = '1F1F0-1F1F7';
        $emoji_flags['KW'] = '1F1F0-1F1FC';
        $emoji_flags['KY'] = '1F1F0-1F1FE';
        $emoji_flags['KZ'] = '1F1F0-1F1FF';
        $emoji_flags['LA'] = '1F1F1-1F1E6';
        $emoji_flags['LB'] = '1F1F1-1F1E7';
        $emoji_flags['LC'] = '1F1F1-1F1E8';
        $emoji_flags['LI'] = '1F1F1-1F1EE';
        $emoji_flags['LK'] = '1F1F1-1F1F0';
        $emoji_flags['LR'] = '1F1F1-1F1F7';
        $emoji_flags['LS'] = '1F1F1-1F1F8';
        $emoji_flags['LT'] = '1F1F1-1F1F9';
        $emoji_flags['LU'] = '1F1F1-1F1FA';
        $emoji_flags['LV'] = '1F1F1-1F1FB';
        $emoji_flags['LY'] = '1F1F1-1F1FE';
        $emoji_flags['MA'] = '1F1F2-1F1E6';
        $emoji_flags['MC'] = '1F1F2-1F1E8';
        $emoji_flags['MD'] = '1F1F2-1F1E9';
        $emoji_flags['ME'] = '1F1F2-1F1EA';
        $emoji_flags['MF'] = '1F1F2-1F1EB';
        $emoji_flags['MG'] = '1F1F2-1F1EC';
        $emoji_flags['MH'] = '1F1F2-1F1ED';
        $emoji_flags['MK'] = '1F1F2-1F1F0';
        $emoji_flags['ML'] = '1F1F2-1F1F1';
        $emoji_flags['MM'] = '1F1F2-1F1F2';
        $emoji_flags['MN'] = '1F1F2-1F1F3';
        $emoji_flags['MO'] = '1F1F2-1F1F4';
        $emoji_flags['MP'] = '1F1F2-1F1F5';
        $emoji_flags['MQ'] = '1F1F2-1F1F6';
        $emoji_flags['MR'] = '1F1F2-1F1F7';
        $emoji_flags['MS'] = '1F1F2-1F1F8';
        $emoji_flags['MT'] = '1F1F2-1F1F9';
        $emoji_flags['MU'] = '1F1F2-1F1FA';
        $emoji_flags['MV'] = '1F1F2-1F1FB';
        $emoji_flags['MW'] = '1F1F2-1F1FC';
        $emoji_flags['MX'] = '1F1F2-1F1FD';
        $emoji_flags['MY'] = '1F1F2-1F1FE';
        $emoji_flags['MZ'] = '1F1F2-1F1FF';
        $emoji_flags['NA'] = '1F1F3-1F1E6';
        $emoji_flags['NC'] = '1F1F3-1F1E8';
        $emoji_flags['NE'] = '1F1F3-1F1EA';
        $emoji_flags['NF'] = '1F1F3-1F1EB';
        $emoji_flags['NG'] = '1F1F3-1F1EC';
        $emoji_flags['NI'] = '1F1F3-1F1EE';
        $emoji_flags['NL'] = '1F1F3-1F1F1';
        $emoji_flags['NO'] = '1F1F3-1F1F4';
        $emoji_flags['NP'] = '1F1F3-1F1F5';
        $emoji_flags['NR'] = '1F1F3-1F1F7';
        $emoji_flags['NU'] = '1F1F3-1F1FA';
        $emoji_flags['NZ'] = '1F1F3-1F1FF';
        $emoji_flags['OM'] = '1F1F4-1F1F2';
        $emoji_flags['PA'] = '1F1F5-1F1E6';
        $emoji_flags['PE'] = '1F1F5-1F1EA';
        $emoji_flags['PF'] = '1F1F5-1F1EB';
        $emoji_flags['PG'] = '1F1F5-1F1EC';
        $emoji_flags['PH'] = '1F1F5-1F1ED';
        $emoji_flags['PK'] = '1F1F5-1F1F0';
        $emoji_flags['PL'] = '1F1F5-1F1F1';
        $emoji_flags['PM'] = '1F1F5-1F1F2';
        $emoji_flags['PN'] = '1F1F5-1F1F3';
        $emoji_flags['PR'] = '1F1F5-1F1F7';
        $emoji_flags['PS'] = '1F1F5-1F1F8';
        $emoji_flags['PT'] = '1F1F5-1F1F9';
        $emoji_flags['PW'] = '1F1F5-1F1FC';
        $emoji_flags['PY'] = '1F1F5-1F1FE';
        $emoji_flags['QA'] = '1F1F6-1F1E6';
        $emoji_flags['RE'] = '1F1F7-1F1EA';
        $emoji_flags['RO'] = '1F1F7-1F1F4';
        $emoji_flags['RS'] = '1F1F7-1F1F8';
        $emoji_flags['RU'] = '1F1F7-1F1FA';
        $emoji_flags['RW'] = '1F1F7-1F1FC';
        $emoji_flags['SA'] = '1F1F8-1F1E6';
        $emoji_flags['SB'] = '1F1F8-1F1E7';
        $emoji_flags['SC'] = '1F1F8-1F1E8';
        $emoji_flags['SD'] = '1F1F8-1F1E9';
        $emoji_flags['SE'] = '1F1F8-1F1EA';
        $emoji_flags['SG'] = '1F1F8-1F1EC';
        $emoji_flags['SH'] = '1F1F8-1F1ED';
        $emoji_flags['SI'] = '1F1F8-1F1EE';
        $emoji_flags['SJ'] = '1F1F8-1F1EF';
        $emoji_flags['SK'] = '1F1F8-1F1F0';
        $emoji_flags['SL'] = '1F1F8-1F1F1';
        $emoji_flags['SM'] = '1F1F8-1F1F2';
        $emoji_flags['SN'] = '1F1F8-1F1F3';
        $emoji_flags['SO'] = '1F1F8-1F1F4';
        $emoji_flags['SR'] = '1F1F8-1F1F7';
        $emoji_flags['SS'] = '1F1F8-1F1F8';
        $emoji_flags['ST'] = '1F1F8-1F1F9';
        $emoji_flags['SV'] = '1F1F8-1F1FB';
        $emoji_flags['SX'] = '1F1F8-1F1FD';
        $emoji_flags['SY'] = '1F1F8-1F1FE';
        $emoji_flags['SZ'] = '1F1F8-1F1FF';
        $emoji_flags['TC'] = '1F1F9-1F1E8';
        $emoji_flags['TD'] = '1F1F9-1F1E9';
        $emoji_flags['TF'] = '1F1F9-1F1EB';
        $emoji_flags['TG'] = '1F1F9-1F1EC';
        $emoji_flags['TH'] = '1F1F9-1F1ED';
        $emoji_flags['TJ'] = '1F1F9-1F1EF';
        $emoji_flags['TK'] = '1F1F9-1F1F0';
        $emoji_flags['TL'] = '1F1F9-1F1F1';
        $emoji_flags['TM'] = '1F1F9-1F1F2';
        $emoji_flags['TN'] = '1F1F9-1F1F3';
        $emoji_flags['TO'] = '1F1F9-1F1F4';
        $emoji_flags['TR'] = '1F1F9-1F1F7';
        $emoji_flags['TT'] = '1F1F9-1F1F9';
        $emoji_flags['TV'] = '1F1F9-1F1FB';
        $emoji_flags['TW'] = '1F1F9-1F1FC';
        $emoji_flags['TZ'] = '1F1F9-1F1FF';
        $emoji_flags['UA'] = '1F1FA-1F1E6';
        $emoji_flags['UG'] = '1F1FA-1F1EC';
        $emoji_flags['UM'] = '1F1FA-1F1F2';
        $emoji_flags['UY'] = '1F1FA-1F1FE';
        $emoji_flags['UZ'] = '1F1FA-1F1FF';
        $emoji_flags['VA'] = '1F1FB-1F1E6';
        $emoji_flags['VC'] = '1F1FB-1F1E8';
        $emoji_flags['VE'] = '1F1FB-1F1EA';
        $emoji_flags['VG'] = '1F1FB-1F1EC';
        $emoji_flags['VI'] = '1F1FB-1F1EE';
        $emoji_flags['VN'] = '1F1FB-1F1F3';
        $emoji_flags['VU'] = '1F1FB-1F1FA';
        $emoji_flags['WF'] = '1F1FC-1F1EB';
        $emoji_flags['WS'] = '1F1FC-1F1F8';
        $emoji_flags['XK'] = '1F1FD-1F1F0';
        $emoji_flags['YE'] = '1F1FE-1F1EA';
        $emoji_flags['YT'] = '1F1FE-1F1F9';
        $emoji_flags['ZA'] = '1F1FF-1F1E6';
        $emoji_flags['ZM'] = '1F1FF-1F1F2';
        $emoji_flags['ZW'] = '1F1FF-1F1FC';
        $emoji_flags['AN'] = '1F1F3-1F1F1';
        $emoji_flags['LTC'] = '1FA99';
        $emoji_flags['NMC'] = '1FA99';
        $emoji_flags['PPC'] = '1FA99';
        $emoji_flags['NVC'] = '1FA99';
        $emoji_flags['XPM'] = '1FA99';
        $emoji_flags['EAC'] = '1FA99';
        $emoji_flags['VTC'] = '1FA99';
        $emoji_flags['EMC'] = '1FA99';
        $emoji_flags['FCT'] = '1FA99';
        $emoji_flags['XRP'] = '1FA99';
        $emoji_flags['XMR'] = '1FA99';
        $emoji_flags['BTS'] = '1FA99';
        $emoji_flags['DASH'] = '1FA99';
        $emoji_flags['DOGE'] = '1FA99';
        $emoji_flags['ETH'] = '1FA99';
        $emoji_flags['NXT'] = '1FA99';
        $emoji_flags['STR'] = '1FA99';
        $emoji_flags['LD'] = '1F3AE';
        $emoji_flags['XAU'] = '1FA99';
        $emoji_flags['XAG'] = '1FA99';
        $emoji_flags['XPD'] = '1F48E';
        $emoji_flags['XPT'] = '1F48E';
        $emoji_flags['XDR'] = '1F310';
        $emoji_flags['XPF'] = '1F3E6';
        $emoji_flags['XCD'] = '1F3E6';
        $emoji_flags['XOF'] = '1F3E6';
        $emoji_flags['RUP'] = '1F3F4';
        $emoji_flags['RUB'] = '1F3F4';
        $emoji_flags['RUR'] = '1F3F4';
        $emoji_flags['XX'] = '1F47D';
        $emoji_flags['WAUA'] = '1F47D';
        $emoji_flags['BNB'] = '1F47D';

        return $emoji_flags;
    }
}
