<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General;

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
        $get_sources = Sources::get_sources();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates'); ?>
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
				<li><b><?php _e('Check Date', Plugin::PLUGIN_SLUG); ?></b>:  <?php echo get_date_from_gmt($rates['data'][0]['put_time'], 'Y-m-d H:i:s'); ?></li>
				<li><b><?php _e('Local Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][0]['local_time'], 'Y-m-d H:i:s'); ?></li>
			</ul></div>

			<div class="col p-0">
			<b><?php _e('Close rate (yesterday)', Plugin::PLUGIN_SLUG); ?></b>
			<ul>

				<li><b><?php _e('Check Date', Plugin::PLUGIN_SLUG); ?></b>:  <?php echo get_date_from_gmt($rates['data'][1]['put_time'], 'Y-m-d H:i:s'); ?></li>
				<li><b><?php _e('Local Date', Plugin::PLUGIN_SLUG); ?></b>: <?php echo get_date_from_gmt($rates['data'][1]['local_time'], 'Y-m-d H:i:s'); ?></li>
			</ul></div></div>
			
			<p class="m-0"><b><?php _e('If you want to force an update, click on the button "Save".', Plugin::PLUGIN_SLUG); ?></b></p>
	
			</div></div>



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


		</div>
		<?php
    }

    public static function update_rates_on_load($some)
    {
        if (!empty($_GET['settings-updated']) && 'true' === $_GET['settings-updated']) {
            UpdateDataSources::update();
        }
    }
}
