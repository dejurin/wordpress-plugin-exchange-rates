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
        $get_currencies = Currencies::get_currencies();
        $get_sources = Sources::get_sources();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');
        $settings = get_option(Settings::$option_name, []);
        $settings = wp_parse_args($settings, Settings::get_defaults()); ?>
		<div class="wrap">
			<h1><?php echo Plugin::NAME; ?></h1>
			<hr>
			<div class="row">
				<div class="col">
					<form action="options.php" method="post">
						<p><?php _e('If you need mid-exchange rates of all currencies, select the source CurrencyRate.Today.', Plugin::PLUGIN_SLUG); ?></p>
						<?php
                        settings_fields(Plugin::PLUGIN_SLUG.'-general');
        do_settings_sections(Plugin::PLUGIN_SLUG.'-general');
        submit_button(__('Save', Plugin::PLUGIN_SLUG)); ?>
					</form>
				</div>
				<div class="col">
					<h3><?php echo $get_sources[$rates['source']]['name']; ?></h3>
					<div class="row">
						<div class="col p-0">
							<b><?php _e('Rate (today)', Plugin::PLUGIN_SLUG); ?></b>
							<ul>
								<li><b><?php _e('Check Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][0]['put_time'], 'Y-m-d H:i:s'); ?></li>
								<li><b><?php _e('Local Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][0]['local_time'], 'Y-m-d H:i:s'); ?></li>
							</ul>
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
					<p class="m-0"><b><?php _e('If you want to force an update, click on the button "Save".', Plugin::PLUGIN_SLUG); ?></b></p>
				</div>
			</div>
			<hr>
			<div class="row">
				<div class="col">
					<h2 id="source-table"><?php echo _e('Source table', Plugin::PLUGIN_SLUG); ?></h2>
					<p><?php _e('List of sources that you can select as a source of exchange rate data.', Plugin::PLUGIN_SLUG); ?></p>
					<?php Sections\SourceTable::init(); ?>
				</div>
				<div class="col">
					<h2 id="currency-table"><?php echo _e('Currency Table', Plugin::PLUGIN_SLUG); ?></h2>
					<p><?php _e('List of currencies that are supported by the current exchange rate data source.', Plugin::PLUGIN_SLUG); ?></p>
					<?php Sections\CurrencyTable::init(); ?>
				</div>
			</div>
			<hr>
			<h2><?php echo _e('Badge shortcode generator ', Plugin::PLUGIN_SLUG); ?></h2>
			<div class="row">
				<div class="col p-0">
					<form  id="shortcode-generator-badge">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-color"><?php echo _e('Color badge', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<input type="text" id="shortcode-badge-color" name="color" value="#024bf4">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-amount"><?php echo _e('Amount', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<input type="text" id="shortcode-badge-amount" name="amount" value="1">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="shortcode-badge-base_currency"><?php echo _e('Base Currency', Plugin::PLUGIN_SLUG); ?></label>
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
									<label for="shortcode-badge-currency_list"><?php echo _e('Currency list', Plugin::PLUGIN_SLUG); ?></label>
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
									<label><?php echo _e('Flag type', Plugin::PLUGIN_SLUG); ?></label>
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
									<label for="shortcode-badge-decimals"><?php echo _e('Decimals', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<input id="shortcode-badge-decimals" name="decimals" type="range" step="1" min="0" max="7" value="<?php echo $settings['decimals']; ?>">
									<span id="shortcode-badge-decimals-show"><?php echo $settings['decimals']; ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php echo _e('Options', Plugin::PLUGIN_SLUG); ?></label>
								</th>
								<td>
									<?php $checkbox = array_slice(Checkbox::get_list(), 3, -2);
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
				<div class="col p-0">
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
