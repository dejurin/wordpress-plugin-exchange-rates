=== Exchange Rates ===
Contributors: falselight
Tags: currency converter, currency conversion, currency exchange, exchange rates, currencies
Donate link: https://currencyrate.today/
Requires at least: 5.6
Requires PHP: 5.6
Tested up to: 7.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Help visitors convert currencies and compare prices. Converters, tables, and badges, with 86 data sources to choose from. No API key required.

== Description ==

Exchange Rates adds **currency converters**, **exchange-rate tables**, and **compact price badges** to WordPress. Help visitors understand prices and calculate amounts without leaving your website.

Choose from a catalog of **86 exchange-rate sources**, including central banks, commercial banks, currency exchange services, and alternative market sources. Scheduled updates retrieve rates through CurrencyRate.today using the source you select.

**No API key or account is required.**

[See the converter, table, and badges in action](https://yuri.ws/wordpress-plugins/exchange-rates/), or [watch the video walkthrough](https://www.youtube.com/watch?v=FTxvE9oeDyU).

= Three ways to bring currencies into your content =

**Currency Converter**

Let visitors enter an amount, choose currencies, and swap the conversion direction. A practical addition to travel guides, property listings, and websites with an international audience.

Available as a widget and a shortcode.

**Exchange Rate Table**

Display selected currencies together in a configurable table. Choose the base currency, adjust the columns, and optionally let visitors enter an amount to recalculate the table.

Show previous-close values and changes when your selected source supplies them.

Available as a widget and a shortcode.

**Currency Badges**

Place compact currency equivalents alongside a price or amount in your content. Useful for accommodation costs, travel budgets, product descriptions, and service prices.

Create badges with the shortcode generator on the plugin settings page.

= Choose the data source that fits your audience =

Different websites need different rates.

Choose a central bank for its published reference rates, a commercial bank or exchange service for its quoted rates, or an alternative market source where available.

Converters, tables, and badges share the same source selection. Currency coverage, availability, and publication schedules depend on that source.

Source captions include a rate date and links to currency-rate information, helping visitors understand where the displayed rates come from.

The complete source catalog is available in the FAQ below.

= Make the display your own =

* **Currencies and amounts:** select the currencies and starting amounts relevant to your content.
* **Number formatting:** choose decimal precision and separator styles.
* **Table layout:** customize columns, headings, borders, and display options.
* **Currency icons:** choose rectangular, square, circular, or emoji flags, or hide flags where supported.
* **Badge appearance:** configure colors and currency labels.
* **Flexible placement:** use widgets in your theme's widget areas or shortcodes in posts and pages.

= Common use cases =

* Travel websites and blogs: help readers compare budgets and local costs.
* News websites and financial portals: publish a selected exchange-rate table.
* Property and accommodation websites: show reference amounts for international visitors.
* Product and service pages: display currency equivalents alongside a quoted price.

= Getting started =

Open Settings → Exchange Rates to choose your data source and review its available currencies.

Add a Currency Converter or Currency Table widget under Appearance → Widgets, or insert a shortcode into a post or page. In the block editor, use a Shortcode block.

The plugin settings page also includes a generator for currency badges.

= Support and development =

Need help choosing a source or configuring a display? Visit the [support forum](https://wordpress.org/support/plugin/exchange-rates/).

Exchange Rates is free and open source. Follow development, report bugs, or contribute through the [GitHub repository](https://github.com/dejurin/wordpress-plugin-exchange-rates).

If the plugin is useful to you, please consider [leaving a review](https://wordpress.org/support/plugin/exchange-rates/reviews/#new-post).

== Installation ==

= Install from your WordPress dashboard =

1. Go to Plugins → Add New.
2. Search for "Exchange Rates".
3. Click Install Now, then Activate.
4. Open Settings → Exchange Rates and choose your data source.
5. Save your settings and check that exchange-rate data is available.
6. Add a widget or shortcode, then preview the result on your site.

= Install from a ZIP file =

1. Download the plugin ZIP file.
2. Go to Plugins → Add New → Upload Plugin.
3. Select the ZIP file, click Install Now, and activate the plugin.
4. Open Settings → Exchange Rates to configure the data source.
5. Add a widget or shortcode and preview the result.

You can also extract the ZIP file and upload the exchange-rates directory to /wp-content/plugins/, then activate the plugin from the Plugins screen.

= Add a widget =

For themes with widget areas, open Appearance → Widgets and add Currency Converter or Currency Table.

In the block-based widgets editor, select the widget through the Legacy Widget block.

For content edited with blocks, use one of the shortcodes below in a Shortcode block.

== Frequently Asked Questions ==

= Do I need an API key or an account? =

No. The plugin retrieves exchange-rate data from the public CurrencyRate service without requiring an API key or account.

Your WordPress server needs an internet connection to retrieve new data.

= How often are exchange rates updated? =

The plugin schedules an update once per hour through WordPress Cron.

The actual execution time depends on your site's WP-Cron configuration and traffic. Each data source also has its own publication schedule, so an hourly request does not necessarily mean that new rates are available.

The converter recalculates amounts immediately using the rates loaded with the page. It does not request a new market quote for every conversion.

= How do I choose a data source? =

Open Settings → Exchange Rates, select a source, and save your settings.

Review the currency list and rate information shown on the settings page to check that the source provides the data you need.

The selected source is shared by the plugin's converters, tables, and badges. You can use different display settings and currency lists for individual displays.

= What should I check after changing a source? =

After saving, confirm that the update succeeded in Settings → Exchange Rates. Review the source name, rate dates, and available currencies before using the displayed conversions.

In version 1.4.0, a failed source change can leave previously stored rates displayed with incorrect source attribution or conversion. Do not rely on those conversions until the selected source updates successfully. Retry with Force Data Update when available, or reselect the previous source and confirm that its update succeeds.

= Can I refresh the data manually? =

Yes. Open Settings → Exchange Rates and use Force Data Update when the button is available.

Manual requests are subject to a one-minute interval after a successful update.

= Can I use shortcodes in posts and pages? =

Yes. In the block editor, add a Shortcode block and paste the shortcode into it.

Currency converter:

    [exchange-rates_currency-converter title="Currency Converter" amount="100" from="USD" to="EUR"]

Exchange-rate table:

    [exchange-rates_currency-table base_currency="USD" currency_list="EUR,GBP,JPY" amount="1"]

Exchange-rate table with an editable amount:

    [exchange-rates_currency-table base_currency="USD" currency_list="EUR,GBP,JPY" amount="100" amount_active="true"]

Currency badges:

    [exchange-rates_badge base_currency="USD" currency_list="EUR,GBP,JPY" amount="100" code="true"]

Use currencies available from your selected source. You can review that source's currency list on the plugin settings page.

= Can I display more than one table on a page? =

Yes. Tables can use different currency lists, starting amounts, and display settings.

Changing the amount in one table recalculates that table only.

= How should visitors enter amounts in an editable table? =

Enter positive numbers without thousands separators or currency symbols. Use a period for decimals, for example 1000 or 1000.50.

In version 1.4.0, table inputs do not correctly parse locale-formatted amounts such as 1,000 or 1 000; these can be read as 1. Number-format settings control the displayed results, not the accepted input format.

= Does this change my store's checkout currency or product prices? =

No. Exchange Rates displays conversions and reference amounts. It does not change your store's checkout currency, payment processing, or stored product prices.

Badge amounts are supplied through the shortcode; they are not automatically synchronized with a product's price.

= Why are some currencies or previous-close values unavailable? =

Each source provides its own set of currencies and rate information.

A currency listed in the plugin's catalog is not necessarily available from every source. Previous-close values and changes also depend on the data supplied.

Check the selected source and its available currency list in Settings → Exchange Rates.

= Can page caching affect the displayed rates? =

Yes. A page cache or CDN can continue serving a previously generated page after the plugin has downloaded new rates.

Configure the cache lifetime or purge the relevant pages so visitors receive updated content.

After updating the plugin, purge cached pages and regenerate combined or minified assets so visitors receive the updated scripts.

= Why are there source links below the displays? =

Converters, tables, and badges include source captions to explain the origin and date of the rates. These captions link to currency-rate information on CurrencyRate.today or CurrencyConvert.online.

The source-information panel also includes a link to the source website, check and latest-change times, and the API provider. It contains a rate disclaimer and a link to the plugin's WordPress.org page.

= Which external services does the plugin use? =

Exchange-rate data and source information are retrieved through [api-bank.fex.to](https://api-bank.fex.to/), the API interface for CurrencyRate.today.

Requests are made from your WordPress server. Rate requests can occur on activation, scheduled updates, source changes, manual refreshes, or a page load when the last successful update is more than an hour old. The settings page also requests source information when its cached copy needs refreshing.

A rate request identifies the selected source. By default, the WordPress HTTP API sends the site's URL and WordPress version in the User-Agent header. The API server also receives the requesting server's IP address.

Amounts entered into the converter are calculated in the visitor's browser; they are not sent to the exchange-rate API.

When emoji flags are selected, the visitor's browser loads flag images from s.w.org, which receives the visitor's IP address and the browser's request headers. Other flag styles are included with the plugin.

For information about the data provider, see [CurrencyRate.Today](https://currencyrate.today/), its [Privacy Policy](https://currencyrate.today/page/privacy), and its [Legal Disclaimer](https://currencyrate.today/page/disclaimer).

= Which data sources are included? =

The plugin's source catalog contains the following 86 entries.

Data is delivered through the CurrencyRate service. Source availability, currency coverage, and publication schedules may vary. This catalog includes central banks, commercial banks, exchange services, alternative market sources, and a virtual currency source.

1. AE — Central Bank of the UAE
2. AL — Bank of Albania
3. AR — Central Bank of Argentina (retail)
4. AR — Central Bank of Argentina (uva)
5. AR — Central Bank of Argentina (wholesale)
6. AR — Cronista Dollar Blue
7. AU — Reserve Bank of Australia
8. AZ — Central Bank of the Republic of Azerbaijan
9. BA — The Central Bank of Bosnia and Herzegovina
10. BD — Bangladesh Bank
11. BG — Bulgarian National Bank
12. BH — Central Bank of Bahrain
13. BR — Banco Central do Brasil (USD)
14. BT — Royal Monetary Authority of Bhutan
15. BY — National Bank of the Republic of Belarus (Belta)
16. CA — Bank of Canada
17. CH — Swiss National Bank
18. CL — Central Bank of Chile
19. CU — Central Bank of Cuba
20. CZ — Czech National Bank
21. DK — Danmarks Nationalbank
22. DZ — Crédit Populaire d’Algérie
23. DZ — Devise Square (Black Market)
24. EG — Central Bank of Egypt
25. ET — National Bank of Ethiopia
26. EU — European Central Bank
27. GB — Bank of England
28. GE — The National Bank of Georgia
29. HU — Hungarian National Bank
30. IL — Bank of Israel
31. IN — Financial Benchmarks India
32. IS — Central Bank of Iceland
33. KH — Bank of Cambodia
34. KR — Seoul Money Brokerage Services
35. KZ — National Bank of Kazakhstan
36. LB — Bank of Lebanon
37. LB — Lebanese Lira Black Market
38. LB — Lebanese Lira Sayrafa Rate
39. MA — BANK AL-MAGHRIB
40. MD — National Bank of Moldova
41. MM — Central Bank of Myanmar
42. MX — Bank of Mexico
43. MX — Bank of Mexico (FIX)
44. NG — Central Bank of Nigeria
45. NG — Naira Black Market Exchange Rates Today
46. NP — Nepal Rastra bank
47. PE — Cambi
48. PE — Cambia FX
49. PE — Cambix
50. PE — Dollar House
51. PE — Hir Power
52. PE — Instakash FX
53. PE — oKane Cambio Online
54. PE — Roblex
55. PE — Srcambio
56. PE — TKambio
57. PE — Yanki
58. PH — Central Bank of the Philippines
59. PL — Polish National Bank
60. RO — Banca Transilvania
61. RO — National Bank of Romania
62. RS — National Bank of Serbia
63. RU — The Central Bank of the Russian Federation
64. SE — Riksbank
65. SG — Monetary Authority of Singapore
66. SS — Bank of South Sudan
67. TH — Bank of Thailand
68. TJ — National Bank of Tajikistan
69. TM — Central Bank of Turkmenistan
70. TN — Central Bank of Tunisia
71. TR — Central Bank of the Republic of Turkey
72. TW — Central Bank of the Republic of China
73. UA — Minfin Blackmarket USD
74. UA — Monobank
75. UA — National Bank of Ukraine
76. UA — PrivatBank
77. UZ — The Central Bank of the Republic of Uzbekistan
78. VE — Central bank of Venezuela
79. VN — Vietcombank
80. ZA — South African Reserve Bank
81. OECS — Eastern Caribbean Central Bank
82. XX — CurrencyRate.Today
83. XX — Transnistrian Republican Bank
84. XX — West African Monetary Agency
85. EG — Egypt Pound Black Market Rate
86. Second Life Game Currency: Linden Dollar

= Which currencies and other units are included? =

The plugin's currency catalog contains the following 199 entries.

The catalog includes national currencies, cryptocurrencies, precious metals, historical currencies, and other units. Inclusion in this list does not guarantee that a source currently supplies a rate for that entry.

Currency codes help distinguish entries with similar names and older denominations. Available choices depend on the selected source.

* AFN — Afghan Afghani
* ALL — Albanian Lek
* DZD — Algerian Dinar
* AOA — Angolan Kwanza
* ARS — Argentine Peso
* AMD — Armenian Dram
* AWG — Aruban Florin
* AUD — Australian Dollar
* AZN — Azerbaijanian Manat
* BSD — Bahamian Dollar
* BHD — Bahraini Dinar
* BDT — Bangladeshi Taka
* BBD — Barbados Dollar
* BYN — Belarusian ruble
* BYR — Belarussian Ruble
* BZD — Belize Dollar
* BMD — Bermudian Dollar
* BTN — Bhutanese Ngultrum
* BNB — Binance Coin
* BTC — Bitcoin
* BTS — BitShares
* BOB — Boliviano
* BWP — Botswana Pula
* BRL — Brazilian Real
* GBP — British Pound
* BND — Brunei Dollar
* BGN — Bulgarian Lev
* BIF — Burundi Franc
* CVE — Cabo Verde Escudo
* KHR — Cambodian Riel
* CAD — Canadian Dollar
* XCG — Caribbean Guilder
* KYD — Cayman Islands Dollar
* XOF — CFA Franc BCEAO
* XAF — CFA Franc BEAC
* XPF — CFP Franc
* CLP — Chilean Peso
* CLF — Chilean Unit of Account
* CNH — Chinese Yuan (offshore)
* COP — Colombian Peso
* KMF — Comoro Franc
* CDF — Congolese Franc
* BAM — Convertible Mark
* CUC — Convertible peso
* NIO — Cordoba Oro
* CRC — Costa Rican Colon
* HRK — Croatian Kuna
* CUP — Cuban Peso
* CZK — Czech Koruna
* DKK — Danish Krone
* DASH — Dash
* DJF — Djibouti Franc
* DOGE — Doge
* DOP — Dominican Peso
* EAC — EAC
* XCD — East Caribbean Dollar
* EGP — Egyptian Pound
* SVC — El Salvador Colon
* EMC — EMC
* ERN — Eritrean Nakfa
* ETH — Ethereum
* ETB — Ethiopian Birr
* EUR — Euro
* FKP — Falkland Islands Pound
* FCT — FCT
* FJD — Fiji Dollar
* GMD — Gambian Dalasi
* GEL — Georgian Lari
* GHS — Ghana Cedi
* GIP — Gibraltar Pound
* XAU — Gold (ounce)
* GTQ — Guatemalan Quetzal
* GGP — Guernsey Pound
* ANG — Guilder
* GNF — Guinea Franc
* GYD — Guyana Dollar
* HTG — Haitian Gourde
* HNL — Honduran Lempira
* HKD — Hong Kong Dollar
* HUF — Hungarian Forint
* ISK — Iceland Krona
* INR — Indian Rupee
* IDR — Indonesian Rupiah
* IRR — Iranian Rial
* IQD — Iraqi Dinar
* ILS — Israeli Shekel
* JMD — Jamaican Dollar
* JPY — Japanese Yen
* JEP — Jersey Pound
* JOD — Jordanian Dinar
* KZT — Kazakhstani Tenge
* KES — Kenyan Shilling
* KWD — Kuwaiti Dinar
* KGS — Kyrgyzstani Som
* LAK — Lao Kip
* LBP — Lebanese Pound
* LSL — Lesotho Loti
* LRD — Liberian Dollar
* LYD — Libyan Dinar
* LD — Linden Dollar
* LTC — Litecoin
* MOP — Macanese Pataca
* MKD — Macedonian Denar
* MGA — Malagasy Ariary
* MWK — Malawian Kwacha
* MYR — Malaysian Ringgit
* MVR — Maldivian Rufiyaa
* IMP — Manx pound
* MRO — Mauritanian Ouguiya
* MRU — Mauritanian Ouguiya
* MUR — Mauritius Rupee
* MXN — Mexican Peso
* MDL — Moldovan Leu
* XMR — Monero
* MNT — Mongolian Tugrik
* MAD — Moroccan Dirham
* MZN — Mozambique Metical
* MMK — Myanmar Kyat
* NAD — Namibia Dollar
* NPR — Nepalese Rupee
* TWD — New Taiwan Dollar
* NZD — New Zealand Dollar
* NGN — Nigerian Naira
* NMC — NMC
* KPW — North Korean Won
* NOK — Norwegian Krone
* NVC — NVC
* NXT — NXT
* PKR — Pakistani Rupee
* XPD — Palladium (ounce)
* PAB — Panamanian Balboa
* PGK — Papua New Guinean Kina
* PYG — Paraguayan Guarani
* PEN — Peruvian Sol
* UYU — Peso Uruguayo
* PHP — Philippine Peso
* XPT — Platinum (ounce)
* PLN — Polish Zloty
* PPC — PPC
* QAR — Qatari Rial
* CNY — Renminbi
* OMR — Rial Omani
* XRP — Ripple
* RON — Romanian Leu
* RUB — Russian Ruble
* RWF — Rwanda Franc
* SHP — Saint Helena Pound
* WST — Samoan Tala
* STD — Sao Tomean Dobra
* STN — Sao Tomean Dobra
* SAR — Saudi Riyal
* RSD — Serbian Dinar
* SCR — Seychelles Rupee
* SLL — Sierra Leonean Leone
* XAG — Silver (ounce)
* SGD — Singapore Dollar
* SBD — Solomon Islands Dollar
* SOS — Somali Shilling
* ZAR — South African Rand
* KRW — South Korean Won
* SSP — South Sudanese Pound
* XDR — Special Drawing Rights
* LKR — Sri Lanka Rupee
* STR — STR
* SDG — Sudanese Pound
* SRD — Surinam Dollar
* SZL — Swazi Lilangeni
* SEK — Swedish Krona
* CHF — Swiss Franc
* SYP — Syrian Pound
* TJS — Tajikistani Somoni
* TZS — Tanzanian Shilling
* THB — Thai Baht
* TOP — Tongan Paanga
* PRB — Transnistrian ruble
* TTD — Trinidad and Tobago Dollar
* TND — Tunisian Dinar
* TRY — Turkish Lira
* TMT — Turkmenistan New Manat
* AED — UAE Dirham
* UGX — Uganda Shilling
* UAH — Ukrainian Hryvnia
* USD — US Dollar
* RUR — USSR Ruble
* UZS — Uzbekistan Sum
* VUV — Vanuatu Vatu
* VEF_BLKMKT — VEF BLKMKT
* VEF_DICOM — VEF DICOM
* VEF_DIPRO — VEF DIPRO
* VEF — Venezuelan Bolivar
* VES — Venezuelan Bolivar
* VND — Vietnamese Dong
* VTC — VTC
* WAUA — W-African Unit of Account
* XPM — XPM
* YER — Yemeni Rial
* ZMW — Zambian Kwacha
* ZWL — Zimbabwean Dollar
* ZWG — Zimbabwean ZiG

Alternative market rates, such as Argentina's dollar blue, are selected through the relevant data source.

== Screenshots ==

1. Exchange-rate table with circular currency flags.
2. Currency converter with amount and currency selectors.
3. Exchange-rate table with circular flags and country names.
4. Exchange-rate table with emoji icons and country names.
5. Table shortcode displaying currency codes, previous-close values, and changes.
6. Currency badges with emoji icons.
7. Currency badges with circular flags.
8. Exchange-rate table with an editable amount.
9. Editable table with circular flags, currency codes, previous-close values, and changes.
10. Editable table with rectangular flags, currency codes, previous-close values, and changes.
11. Currency Converter and Currency Table previews in the WordPress widgets editor.
12. Plugin settings, data source selection, and badge shortcode generator.

== Changelog ==

= 1.4.0 =
* Preserve trailing integer zeros when formatting amounts with zero decimal places.
* Keep amount changes and number formatting local to the edited exchange-rate table.
* Associate settings labels with their existing controls while preserving the Sources link.
* Keep administrative JavaScript loop variables local to avoid overwriting other scripts' globals.
* Compare manual-update timestamps in UTC so the one-minute interval is independent of the site timezone.
* Persist successful update metadata through settings sanitization, verify stored data before reporting success, and retain the last successful timestamp after provider failures.
* Refresh a newly selected source after saving the selection, and display unknown update time without inventing a successful-update date.

= 1.3.2 =
* Fixed admin stylesheet and script loading scope: plugin admin assets now load only on the plugin settings page and the widgets screen, so they no longer get injected into the WordPress 7.1 block editor iframe.
* Verified compatibility with WordPress 7.1.

= 1.3.1 =
* Fixed handling of sources that provide current rates without previous-day close data.
* Hardened API response validation before saving exchange-rate data.
* Improved shortcode, widget, admin, and settings sanitization.
* Standardized boolean shortcode flags so only explicit false disables an attribute.
* Improved compatibility metadata for WordPress 7.0 and PHP 5.6+.

= 1.3.0 =
* Added: Caribbean Guilder (XCG) and Zimbabwean ZiG (ZWG).
* Security: fixed vulnerability.
* Minor bug fixes and improvements.

= 1.2.5 =
* Minor bug fixes.

= 1.2.4 =
* Improved compatibility with PHP 8.

= 1.2.3 =
* Security: fixed vulnerability.

= 1.2.2 =
* Minor bug fixes.

= 1.2.1 =
* Added RTL (Right-to-Left) style support.
* Minor bug fixes.
* Tested up to WordPress 6.5.

= 1.2.0 =
* Security: fixed XSS vulnerability.
* Performance optimizations.
* Accessibility improvements.
* Minor bug fixes.
* Tested up to WordPress 6.5.

= 1.1.6 =
* Minor bug fixes.

= 1.1.5 =
* Tested up to WordPress 6.4.
* Added source: 🇪🇬 Egypt Pound Black Market Rate.
* Added source: 🎮 Second Life game currency (Linden Dollar).
* Minor bug fixes.

= 1.1.4 =
* Minor bug fixes.

= 1.1.3 =
* Fixed shortcode generator.

= 1.1.2 =
* Fixed classic widget generator.

= 1.1.1 =
* Improved style compatibility.

= 1.1.0 =
* Added new sources including black markets, blue dollar, etc.
* Added Devises du Square Port-Saïd à Alger.
* Added Dólar Blue.
* Added Black Market Hryvnia.
* Added Black Market Naira.
* Added Sayrafa Rate LBP.
* Simplified translations by removing bank names.
* Minor bug fixes.

= 1.0.4 =
* Improved compatibility with other plugins (thanks @denver28).
* Improved PHP 8 compatibility.
* Minor bug fixes.

= 1.0.3 =
* Minor bug fixes.

= 1.0.2 =
* Added new sources.
* Minor bug fixes.

= 1.0.1 =
* Fixed decimal comma format for “amount form”.
* Fixed CSS caption color.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.4.0 =
Maintenance release for number formatting, independent tables, settings accessibility and reliable manual-update state. Existing settings, shortcodes and widgets are retained; no data migration is required.

= 1.3.1 =
Compatibility and security hardening release for WordPress 7.0. Recommended for all users.

= 1.3.0 =
Important update: added new currencies (Caribbean Guilder, Zimbabwean ZiG) and fixed a security issue. Recommended for all users.

= 1.2.5 =
Maintenance release: minor bug fixes.

= 1.2.4 =
Compatibility update: improved support for PHP 8.

= 1.2.3 =
Security update: vulnerability fixed. Please update immediately.

= 1.2.2 =
Maintenance release: minor bug fixes.

= 1.2.1 =
Added RTL style support and tested up to WordPress 6.5. Minor fixes.

= 1.2.0 =
Major release: fixed XSS vulnerability, improved performance and accessibility, tested up to WordPress 6.5.

= 1.1.5 =
New sources added (Egypt Pound Black Market, Linden Dollar). Tested up to WordPress 6.4.

= 1.1.0 =
Added multiple new exchange rate sources (blue dollar, black markets, Sayrafa rate, etc.). Minor fixes.

= 1.0.4 =
Improved compatibility with other plugins and PHP 8.

= 1.0.0 =
First public release.
