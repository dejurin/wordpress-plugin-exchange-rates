<?php
/** Shared read-only snapshot for guarded disposable-site acceptance tools. */
function er_acceptance_raw_state() {
	global $wpdb;
	$state = array( 'options' => array(), 'pages' => array() );
	foreach ( array( 'exchange-rates', 'exchange-rates_rates', 'widget_exchange-rates_currency-table', 'widget_exchange-rates_currency-converter', 'sidebars_widgets' ) as $name ) {
		$state['options'][ $name ] = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
	}
	$state['pages'] = $wpdb->get_results( "SELECT ID,post_title,post_name,post_content,post_status FROM {$wpdb->posts} WHERE post_type='page' ORDER BY ID", ARRAY_A );
	return $state;
}
