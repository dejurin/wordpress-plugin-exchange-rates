<?php

namespace Dejurin\ExchangeRates\Admin\Settings\General;


use Dejurin\ExchangeRates\Service\UpdateDataSources;
use Dejurin\ExchangeRates\Models\Sources;
use Dejurin\ExchangeRates\Plugin;

class Page
{
    public static function init()
    {
        Sections\SourceSelect::init();
    }

    public static function aj()
    {
        ?><script>
		jQuery(document).ready(function($) {
			jQuery('#test').on("click", function() {
				jQuery.post(ajaxurl, {
					'action': 'my_action',
					'whatever': 1234
				}, function(response) {
					console.log(response);
				});
			});
		});
		</script><?php
    }

    public static function ajx()
    {
        global $wpdb;
        echo UpdateDataSources::update();
        wp_die();
    }

    public static function render()
    {
		$get_sources = Sources::get_sources();
        $rates = get_option(Plugin::PLUGIN_SLUG.'_rates');  ?>
		<div class="wrap">

		
			<h1><?php echo Plugin::NAME; ?></h1>
			<hr>

			<div class="row">
<div class="col">
			<form action="options.php" method="post">
				<?php
                settings_fields(Plugin::PLUGIN_SLUG.'-general');
				do_settings_sections(Plugin::PLUGIN_SLUG.'-general');
				submit_button(); 
				?>
			</form></div>
			<div class="col">
		
			
				<h3><?php _e('Source', Plugin::PLUGIN_SLUG); ?></b>: <?php echo $get_sources[$rates['source']]['name']; ?></h3>

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
			
			<p class="m-0"><button class="button" id="test"><?php _e('UPDATE', Plugin::PLUGIN_SLUG); ?></button>
			<label for="test"><?php _e('Force the update of exchange rates.', Plugin::PLUGIN_SLUG); ?></label></p>
	
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

	public static function update_rates_on_load($some) {
		// Настройки страницы обновляются
		// Значит нужно попробоывать получить ответ от API
		if( !empty( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			\Dejurin\ExchangeRates\Service\UpdateDataSources::update();
		}
	}
}


