<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General;

use Dejurin\ExchangeRates\Models\Checkbox;
use Dejurin\ExchangeRates\Models\Currencies;
use Dejurin\ExchangeRates\Models\Flags;
use Dejurin\ExchangeRates\Models\Settings;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;
use Dejurin\ExchangeRates\Service\UpdateDataSources;

class Page
{
    public static function init()
    {
        Sections\Settings::init();
    }

    public static function render()
    {
        $get_currencies = Currencies::get_list();
        $get_sources = Sources::get_list();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults());
        $get_sources_data = \Dejurin\ExchangeRates\Models\DataSources::getInstance()->get_sources_data();
        $currency_list = array_keys($rates['data'][0]['rates']); ?>
		<div class="wrap">
			<h1>
				<?php echo Plugin::NAME; ?>
			</h1>
			<hr>
			<div class="row">
				<div class="col">
					<p><?php esc_html_e('Advice! If you need mid-exchange rates of all currencies, select the source CurrencyRate.Today.', 'exchange-rates'); ?></p>
					<?php if ($settings['rates_available']) { ?>
					<p><b><?php esc_html_e('Last successful update:', 'exchange-rates'); ?></b> 
					<?php echo esc_html(date('r', $settings['update_timestamp'])); ?></p>
					<?php } ?>
					<form action="options.php" method="post">
						<?php
                        settings_fields(Plugin::PLUGIN_SLUG.'-general');
        				do_settings_sections(Plugin::PLUGIN_SLUG.'-general');
        				submit_button(esc_html__('Save', 'exchange-rates')); ?>
					</form>
				</div>
				<div class="col">
					<h3><?php echo (isset($rates['source'])) ? esc_html($get_sources[$rates['source']]['name']) : esc_html__('No data received', 'exchange-rates'); ?></h3>
					<div class="row">
						<div class="col p-0">
							<b><?php esc_html_e('Rate (today)', 'exchange-rates'); ?></b>
							<?php if (isset($rates['data'][0])) { ?>
							<ul>
								<li><b><?php esc_html_e('Check Date', 'exchange-rates'); ?></b>: <?php echo esc_html(get_date_from_gmt($rates['data'][0]['put_time'], 'Y-m-d H:i:s')); ?></li>
								<li><b><?php esc_html_e('Local Date', 'exchange-rates'); ?></b>: <?php echo esc_html(get_date_from_gmt($rates['data'][0]['local_time'], 'Y-m-d H:i:s')); ?></li>
							</ul>
							<?php } else { ?>
							<p><?php esc_html_e('Rate no data.', 'exchange-rates'); ?></p>
							<?php } ?>
						</div>
						<div class="col p-0">
							<b><?php esc_html_e('Previous close (yesterday)', 'exchange-rates'); ?></b>
							<?php if (isset($rates['data'][1])) { ?>
							<ul>
								<li><b><?php esc_html_e('Check Date', 'exchange-rates'); ?></b>: <?php echo esc_html(get_date_from_gmt($rates['data'][1]['put_time'], 'Y-m-d H:i:s')); ?></li>
								<li><b><?php esc_html_e('Local Date', 'exchange-rates'); ?></b>: <?php echo esc_html(get_date_from_gmt($rates['data'][1]['local_time'], 'Y-m-d H:i:s')); ?></li>
							</ul>
							<?php } else { ?>
							<p><?php esc_html_e('Previous close no data.', 'exchange-rates'); ?></p>
							<?php } ?>
						</div>
					</div>
					<?php if (!isset($rates['source'])) { ?>
						<h2><?php esc_html_e('No data received. Press the "Save" button to force an update of the data.', 'exchange-rates'); ?></h2>
						<p class="m-0"><b><?php esc_html_e('ERROR', 'exchange-rates'); ?>:</b>
							<?php echo esc_html($get_sources_data['error']); ?>
						</p>
					<?php } else { ?>
					<p class="m-0"><b><?php esc_html_e('If you want to force an update, click on the button "Save".', 'exchange-rates'); ?></b></p>
					<?php } ?>
				</div>
			</div>
			<?php if ($rates) { ?>
			<hr>
			<div class="row">
				<div class="col">
					<h2 id="source-table"><?php esc_html_e('Source table', 'exchange-rates'); ?></h2>
					<p><?php esc_html_e('List of sources that you can select as a source of exchange rate data.', 'exchange-rates'); ?></p>
					<?php Sections\SourceTable::init(); ?>
				</div>
				<div class="col">
					<h2 id="currency-table"><?php esc_html_e('Currency Table', 'exchange-rates'); ?></h2>
					<p><?php esc_html_e('List of currencies that are supported by the current exchange rate data source.', 'exchange-rates'); ?></p>
					<?php Sections\CurrencyTable::init(); ?>
				</div>
			</div>
			<?php } ?>
			<hr>
			<div class="row">
				<?php if ($rates) { ?>
				<div class="col p-0">
				<h2><?php esc_html_e('Badge shortcode generator ', 'exchange-rates'); ?></h2>
					<form data-shortcode-generator="<?php echo Plugin::PLUGIN_SLUG; ?>_badge">
					<input type="hidden" name="id_base" value="<?php echo Plugin::PLUGIN_SLUG; ?>_badge"/>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-color"><?php esc_html_e('Color badge', 'exchange-rates'); ?></label>
								</th>
								<td>
									<input type="text" id="shortcode-badge-color" name="color" value="#024bf4">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-amount"><?php esc_html_e('Amount', 'exchange-rates'); ?></label>
								</th>
								<td>
									<input type="text" id="shortcode-badge-amount" name="amount" value="1">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-base_currency"><?php esc_html_e('Base Currency', 'exchange-rates'); ?></label>
								</th>
								<td>
									<select id="shortcode-badge-base_currency" name="base_currency">
										<?php foreach ($currency_list as $value) { printf(
                                                    '<option value="%s"%s>%s</option>',
                                                    esc_attr($value),
                                                    selected($value, $rates['base'], false),
                                                    esc_html($value.' - '.$get_currencies[$value]['name'])
                                                );
        									} ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-currency_list"><?php esc_html_e('Currency list', 'exchange-rates'); ?></label>
								</th>
								<td>
									<select multiple="multiple" class="resize-both" size="10" id="shortcode-badge-currency_list" name="currency_list">
										<?php foreach ($currency_list as $value) {
            														printf(
                                                '<option value="%s"%s>%s</option>',
                                                esc_attr($value),
                                                selected('EUR', $value, false),
                                                esc_html($value.' - '.$get_currencies[$value]['name'])
                                            );
        								} ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e('Flag type', 'exchange-rates'); ?></label>
								</th>
								<td>
									<?php foreach (Flags::get_types() as $item) {
            							echo sprintf(
                                            '<input type="radio" id="shortcode-badge-flag_type-%1$s" name="flag_type" value="%1$s"%3$s><label for="shortcode-badge-flag_type-%1$s">%2$s</label>&nbsp;',
                                            esc_attr($item['value']),
                                            esc_html($item['name']),
                                            checked($item['value'], 'none', false)
                                        );
        							} ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-decimals"><?php esc_html_e('Decimals', 'exchange-rates'); ?></label>
								</th>
								<td>
									<input id="shortcode-badge-decimals" name="decimals" type="range" step="1" min="0" max="7" value="<?php echo esc_attr($settings['decimals']); ?>">
									<span id="shortcode-badge-decimals-show"><?php echo esc_html($settings['decimals']); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e('Options', 'exchange-rates'); ?></label>
								</th>
								<td>
									<?php $checkbox = array_slice(Checkbox::get_list(), 2, 5);
        								foreach ($checkbox as $key => $value) {
            								echo sprintf(
                                            '<p><label><input type="checkbox" name="%1$s">&nbsp;%2$s</label></p>',
                                            esc_attr($key),
                                            esc_html($value)
                                        );
        							} ?>
								</td>
							</tr>
						</tbody>
					</table>
					<input type="hidden" name="id" value="<?php echo time(); ?>">
					<hr>
					<textarea name="shortcode-generator" style="width:100%" rows="3" onclick="this.focus();this.select()" readonly></textarea>
					<div style="float: right"><button class="button button-primary" disabled><?php esc_html_e('Generate', 'exchange-rates'); ?></button></div>
					</form>
					
				</div>
				<?php } ?>
				<div class="col p-0">
					<h1><?php esc_html_e('Help', 'exchange-rates'); ?></h1>
					<p><?php esc_html_e('This Plugin includes two widgets and one shortcode.', 'exchange-rates'); ?></p>
					<h3><?php esc_html_e('Widgets', 'exchange-rates'); ?></h3>
					<ol>
					<li><?php esc_html_e('Currency Converter is easy to use, simple real-time converter;', 'exchange-rates'); ?><br>
					✔&nbsp;<?php esc_html_e('Universal widget, it can be used on travel and property website/blog, as well as online stores.', 'exchange-rates'); ?>
					</li>

					<li><?php esc_html_e('Currency Table is a table with exchange rates and another features.', 'exchange-rates'); ?><br>
					✔&nbsp;<?php esc_html_e('The currency rates table is mainly used by news websites, portals and financial forums.', 'exchange-rates'); ?></li>
					</ol>
					<h3><?php esc_html_e('Shortcode', 'exchange-rates'); ?></h3>
					<ol>
					<li>
					<?php esc_html_e('Badge is a simple exchange rates list.', 'exchange-rates'); ?>
						<br>
						✔&nbsp;<?php esc_html_e('An excellent shortcode that can be used in international online stores. With ease you can add a list of prices in different currencies to the product page.', 'exchange-rates'); ?>
					</li>
					</ol>
					<h3><?php esc_html_e('How to install widget?', 'exchange-rates'); ?></h3>
					<ol>
						<li><?php esc_html_e('Go to Appearance &rarr; Widgets;', 'exchange-rates'); ?></li>
						<li><?php esc_html_e('Legacy Widget &rarr; Choose Widget;', 'exchange-rates'); ?></li>
						<li><?php esc_html_e('Enjoy!', 'exchange-rates'); ?></li>
					</ol>
					<h3><?php esc_html_e('How to install shortcode?', 'exchange-rates'); ?></h3>
					<ol>
						<li><?php esc_html_e('Generate shortcode from this page;', 'exchange-rates'); ?></li>
						<li><?php esc_html_e('Copy and Paste shortcode anywhere you like;', 'exchange-rates'); ?></li>
						<li><?php esc_html_e('Enjoy!', 'exchange-rates'); ?></li>
					</ol>
					<hr>
					<div>
						<div style="font-size:large">
						<ul>
							<li>💹 <?php esc_html_e('Supported by:', 'exchange-rates'); ?> <a href="https://currencyrate.today/" rel="noopener" target="_blank">CurrencyRate.Today</a></li>
							<li>❓ <?php esc_html_e('Questions', 'exchange-rates'); ?>: <a href="https://wordpress.org/support/plugin/exchange-rates/" rel="noopener" target="_blank"><?php esc_html_e('Support', 'exchange-rates'); ?></a></li>
						</ul>
						<p style="font-size:x-large">🥰 <?php esc_html_e('Please rate plugin', 'exchange-rates'); ?>
                                    &laquo;<a href="https://wordpress.org/plugins/<?php echo Plugin::PLUGIN_SLUG; ?>/reviews/#new-post" rel="noopener" target="_blank"><?php echo Plugin::NAME; ?></a>&raquo;
									<a href="https://wordpress.org/support/plugin/<?php echo Plugin::PLUGIN_SLUG; ?>/reviews/#new-post" rel="noopener" target="_blank"><img src="<?php echo plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path); ?>assets/img/stars.png" alt="<?php esc_html_e('Rating', 'exchange-rates'); ?>"></a></p>

						</div>
					</div>
				</div>
			</div>
		</div>
		<p>V<?php echo Plugin::VERSION; ?></p>
<?php
    }

    public static function update_rates_on_load($some)
    {
        if (!empty($_GET['settings-updated']) && 'true' === $_GET['settings-updated']) {
            UpdateDataSources::update();
        }
    }
}
