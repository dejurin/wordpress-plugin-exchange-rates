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
        $get_sources_data = \Dejurin\ExchangeRates\Models\DataSources::getInstance()->get_sources_data(); ?>
		<div class="wrap">
			
			<h1>
				
				<?php echo Plugin::NAME; ?>
			</h1>
			
			<hr>
			<div class="row">
				<div class="col">
						<p><?php _e('Advice! If you need mid-exchange rates of all currencies, select the source CurrencyRate.Today.', Plugin::PLUGIN_SLUG); ?></p>
						<?php if ($settings['rates_available']) { ?>
						<p><b>Last successful update</b>: <?php echo date('r', $settings['update_timestamp']); ?></p>
						<?php } ?>
					<form action="options.php" method="post">
						<?php
                        settings_fields(Plugin::PLUGIN_SLUG.'-general');
        do_settings_sections(Plugin::PLUGIN_SLUG.'-general');
        submit_button(__('Save', Plugin::PLUGIN_SLUG)); ?>
					</form>
				</div>
				<div class="col">
				
					<h3><?php echo (isset($rates['source'])) ? $get_sources[$rates['source']]['name'] : __('No data received', Plugin::PLUGIN_SLUG); ?></h3>
			
					<div class="row">
						<div class="col p-0">
							<b><?php _e('Rate (today)', Plugin::PLUGIN_SLUG); ?></b>
							<?php if (isset($rates['data'][0])) { ?>
							<ul>
								<li><b><?php _e('Check Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][0]['put_time'], 'Y-m-d H:i:s'); ?></li>
								<li><b><?php _e('Local Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][0]['local_time'], 'Y-m-d H:i:s'); ?></li>
							</ul>
							<?php } else { ?>
							<p><?php _e('Rate no data.', Plugin::PLUGIN_SLUG); ?></p>
							<?php } ?>
						</div>
						<div class="col p-0">
							<b><?php _e('Previous close (yesterday)', Plugin::PLUGIN_SLUG); ?></b>
							<?php if (isset($rates['data'][1])) { ?>
							<ul>
								<li><b><?php _e('Check Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][1]['put_time'], 'Y-m-d H:i:s'); ?></li>
								<li><b><?php _e('Local Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][1]['local_time'], 'Y-m-d H:i:s'); ?></li>
							</ul>
							<?php } else { ?>
							<p><?php _e('Previous close no data.', Plugin::PLUGIN_SLUG); ?></p>
							<?php } ?>
						</div>
					</div>
					<?php if (!isset($rates['source'])) { ?>
						<h2><?php _e('No data received. Press the "Save" button to force an update of the data.', Plugin::PLUGIN_SLUG); ?></h2>
						<p class="m-0"><b><?php _e('ERROR', Plugin::PLUGIN_SLUG); ?>:</b>
							<?php echo $get_sources_data['error']; ?>
						</p>
					<?php } else { ?>
					<p class="m-0"><b><?php _e('If you want to force an update, click on the button "Save".', Plugin::PLUGIN_SLUG); ?></b></p>
					<?php } ?>
				
				</div>
			</div>
			<?php if ($rates) { ?>
			<hr>
			<div class="row">
				<div class="col">
					<h2 id="source-table"><?php _e('Source table', Plugin::PLUGIN_SLUG); ?></h2>
					<p><?php _e('List of sources that you can select as a source of exchange rate data.', Plugin::PLUGIN_SLUG); ?></p>
					<?php Sections\SourceTable::init(); ?>
				</div>
				<div class="col">
					<h2 id="currency-table"><?php _e('Currency Table', Plugin::PLUGIN_SLUG); ?></h2>
					<p><?php _e('List of currencies that are supported by the current exchange rate data source.', Plugin::PLUGIN_SLUG); ?></p>
					<?php Sections\CurrencyTable::init(); ?>
				</div>
			</div>
			<?php } ?>
			<hr>
			<div class="row">
				<?php if ($rates) { ?>
				<div class="col p-0">
				<h2><?php _e('Badge shortcode generator ', Plugin::PLUGIN_SLUG); ?></h2>
					<form  id="shortcode-generator-badge">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-color"><?php _e('Color badge', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<input type="text" id="shortcode-badge-color" name="color" value="#024bf4">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-amount"><?php _e('Amount', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<input type="text" id="shortcode-badge-amount" name="amount" value="1">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-base_currency"><?php _e('Base Currency', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<select id="shortcode-badge-base_currency" name="base_currency">
										<?php
                                            $base_currency_list = array_keys($rates['data'][0]['rates']);

        foreach ($base_currency_list as $value) {
            printf(
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
									<label for="shortcode-badge-currency_list"><?php _e('Currency list', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<select multiple="multiple" class="resize-both" size="10" id="shortcode-badge-currency_list" name="currency_list">
										<?php foreach ($base_currency_list as $value) {
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
									<label><?php _e('Flag type', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<?php foreach (Flags::get_types() as $item) {
            echo sprintf(
                                            '<input type="radio" id="shortcode-badge-flag_type-%1$s" name="flag_type" value="%1$s"%3$s><label for="shortcode-badge-flag_type-%1$s">%2$s</label>&nbsp;',
                                            esc_attr($item['value']),
                                            $item['name'],
                                            checked($item['value'], 'none', false)
                                        );
        } ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-decimals"><?php _e('Decimals', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<input id="shortcode-badge-decimals" name="decimals" type="range" step="1" min="0" max="7" value="<?php echo $settings['decimals']; ?>">
									<span id="shortcode-badge-decimals-show"><?php echo $settings['decimals']; ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php _e('Options', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<?php $checkbox = array_slice(Checkbox::get_list(), 5, 5);
        foreach ($checkbox as $key => $value) {
            echo sprintf(
                                            '<p><input type="checkbox" id="shortcode-badge-%1$s" name="%1$s"><label for="shortcode-badge-%1$s">%2$s</label></p>',
                                            esc_attr($key),
                                            $value
                                        );
        } ?>
								</td>
							</tr>
						</tbody>
					</table>
					<input type="hidden" name="id" value="<?php echo time(); ?>">
					</form>
					<hr>
					<textarea id="shortcode-generator-badge-textarea" style="width:100%" rows="3" onclick="this.focus();this.select()" readonly></textarea>
					
				</div>
				<?php } ?>
				<div class="col p-0">
					<h2><?php _e('Help', Plugin::PLUGIN_SLUG); ?></h2>
					<p><?php _e('This Plugin includes two widgets and one shortcode.', Plugin::PLUGIN_SLUG); ?></p>

					<h3><?php _e('Widgets', Plugin::PLUGIN_SLUG); ?></h3>
					<ol>
					<li><?php _e('Currency Converter is easy to use, simple real-time converter;', Plugin::PLUGIN_SLUG); ?><br>
					&#x2714&nbsp;<?php _e('Universal widget, it can be used on travel and property website/blog, as well as online stores.', Plugin::PLUGIN_SLUG); ?>
					</li>

					<li><?php _e('Currency Table is a table with exchange rates and another features.', Plugin::PLUGIN_SLUG); ?><br>
					&#x2714&nbsp;<?php _e('The currency rates table is mainly used by news websites, portals and financial forums.', Plugin::PLUGIN_SLUG); ?></li>
					</ol>
					<h3><?php _e('Shortcode', Plugin::PLUGIN_SLUG); ?></h3>
					<ol>
					<li>
					<?php _e('Badge is a simple exchange rates list.', Plugin::PLUGIN_SLUG); ?>
						<br>
						&#x2714&nbsp;<?php _e('An excellent shortcode that can be used in international online stores. With ease you can add a list of prices in different currencies to the product page.', Plugin::PLUGIN_SLUG); ?>
					</li>
					</ol>
					<h3><?php _e('How to install widget?', Plugin::PLUGIN_SLUG); ?></h3>
					<ol>
						<li><?php _e('Go to Appearance &rarr; Widgets;', Plugin::PLUGIN_SLUG); ?></li>
						<li><?php _e('Legacy Widget &rarr; Choose Widget;', Plugin::PLUGIN_SLUG); ?></li>
						<li><?php _e('Enjoy!', Plugin::PLUGIN_SLUG); ?></li>
					</ol>
					<h3><?php _e('How to install shortcode?', Plugin::PLUGIN_SLUG); ?></h3>
					<ol>
						<li><?php _e('Generate shortcode from this page;', Plugin::PLUGIN_SLUG); ?></li>
						<li><?php _e('Copy and Paste shortcode anywhere you like;', Plugin::PLUGIN_SLUG); ?></li>
						<li><?php _e('Enjoy!', Plugin::PLUGIN_SLUG); ?></li>
					</ol>
					<hr>
					<div>
						<div>
							<img width="96" height="96" style="float:left;padding-right:15px" src="<?php echo plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path); ?>assets/img/bank.svg" />
						</div>
						<div>
						<ul>
							<li>&#x2753; Feel free, write if you will have any questions: <a href="https://t.me/converter_support" target="_blank">Online support</a></li>
							<li>&#x1F4B0; Your might like it: <a href="https://wordpress.org/plugins/exchange-rates/" target="_blank">WP Plugin page</a></li>
							<li>&#x1F4B9; Supported by: <a href="https://currencyrate.today/" target="_blank">CurrencyRate</a></li>
							<li>&#x1F4B5; Fiat money: <a href="https://moneyconvert.net/" target="_blank">MoneyConvert.net</a></li>
						</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			jQuery(document).ready(function() {
					jQuery('#shortcode-badge-color').wpColorPicker({
						change: function(event, ui){
							shortcodeGeneratorBadge('form#shortcode-generator-badge');
							jQuery('#shortcode-badge-color').val(ui.color.toString())
							console.log(ui.color.toString())
						},
						border: true,
						palettes: false,
					});
				function shortcodeGeneratorBadge(_this) {
					var serializeArray = jQuery(_this).serializeArray();
					var line = '[<?php echo Plugin::PLUGIN_SLUG; ?>-badge ';
					var currency_list = '';
					jQuery.each(serializeArray, function(index, attr) {
						if (attr.name === 'currency_list') {
							currency_list += attr.value + ',';
						} else {
							value = attr.value;
							line += attr.name + '="' + value + '" ';
						}
					});
					line += 'currency_list="' + currency_list.slice(0, currency_list.length - 1) + '"';
					line += ']';
					jQuery('#shortcode-generator-badge-textarea').text(line);
				}
				jQuery('form#shortcode-generator-badge').on("input", function(event) {
					shortcodeGeneratorBadge(this);
				});
				shortcodeGeneratorBadge('form#shortcode-generator-badge');
			});
		</script>
<?php
    }

    public static function update_rates_on_load($some)
    {
        if (!empty($_GET['settings-updated']) && 'true' === $_GET['settings-updated']) {
            UpdateDataSources::update();
        }
    }
}
