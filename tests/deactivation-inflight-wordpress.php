<?php
/** Observational probe: no production fixes, disposable test site only. */
if ('1' !== getenv('EXCHANGE_RATES_TEST_MODE') || 'local' !== wp_get_environment_type()) { throw new RuntimeException('Isolated test only.'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function er_probe_child($mode) {
 $r = WP_CLI::runcommand('eval-file ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($mode), array('return'=>'all','exit_error'=>false,'launch'=>true));
 if (0 !== $r->return_code || '' !== trim($r->stderr)) { throw new RuntimeException($r->stdout.$r->stderr); }
 return json_decode($r->stdout,true);
}
function er_probe_state() {
 $events=array();
 foreach (_get_cron_array() as $timestamp=>$hooks) { foreach ($hooks as $hook=>$entries) { if (0===strpos($hook,'exchange-rates_data_')) { foreach($entries as $event) { $events[]=array('hook'=>$hook,'recurrence'=>$event['schedule'],'args'=>$event['args']); } } } }
 return array('active'=>is_plugin_active('exchange-rates/Install.php'),'events'=>$events,'retry_callback'=>has_action('exchange-rates_data_retry'),'hourly_callback'=>has_action('exchange-rates_data_update'),'backoff'=>false!==get_transient('exchange-rates_refresh_after'),'lock'=>get_option('exchange-rates_refresh_lock'),'rates'=>get_option('exchange-rates_rates'),'settings'=>get_option('exchange-rates'));
}
$mode=isset($args[0])?$args[0]:'run';
if ('deactivate'===$mode) { deactivate_plugins('exchange-rates/Install.php'); echo wp_json_encode(er_probe_state()); return; }
if ('inspect'===$mode) { echo wp_json_encode(er_probe_state()); return; }
if ('dispatch'===$mode) {
 $http=0;
 add_filter('pre_http_request',function($pre,$args,$url)use(&$http){if(0===strpos($url,'https://api-bank.fex.to/')){++$http;} return $pre;},99,3);
 $command=WP_CLI::runcommand('cron event run exchange-rates_data_retry',array('return'=>'all','exit_error'=>false,'launch'=>false));
 echo wp_json_encode(array('exit'=>$command->return_code,'http'=>$http,'after'=>er_probe_state()));return;
}
$names=array('exchange-rates','exchange-rates_rates','cron','active_plugins','exchange-rates_refresh_lock','exchange-rates_refresh_generation');$original=array();foreach($names as $name){$original[$name]=get_option($name);}
$transients=array();foreach(array('exchange-rates_refresh_after','exchange-rates_sources')as$name){$transients[$name]=get_transient($name);}
$reports=array();
try {
 foreach(array('failure','success')as$outcome){
  update_option('active_plugins',$original['active_plugins']);
  update_option('exchange-rates',er_source_settings('currencyrate'));update_option('exchange-rates_rates',er_source_payload('currencyrate'));
  \Dejurin\ExchangeRates\Cron\UpdateRates::clear_tasks();\Dejurin\ExchangeRates\Cron\UpdateRates::register_task();
  $old=get_option('exchange-rates_rates');$during=null;
  $filter=function($pre,$args,$url)use($outcome,&$during){
   if(0!==strpos($url,'https://api-bank.fex.to/rates/')){return $pre;}
   $during=er_probe_child('deactivate');
   if('failure'===$outcome){return new WP_Error('fixture','Controlled failure after deactivation.');}
   $payload=er_source_payload('currencyrate');$payload['data'][0]['rates']['EUR']=1.5;
   return array('headers'=>array(),'body'=>wp_json_encode($payload),'response'=>array('code'=>200),'cookies'=>array());
  };
  add_filter('pre_http_request',$filter,99,3);
  try{$result=\Dejurin\ExchangeRates\Cron\UpdateRates::run_hourly();}finally{remove_filter('pre_http_request',$filter,99);}
  $after=er_probe_child('inspect');
  $report=array('outcome'=>$outcome,'result'=>$result,'events_immediately_after_deactivation'=>$during['events'],'events_after_worker_returns'=>$after['events'],'active'=>$after['active'],'retry_callback_in_new_request'=>$after['retry_callback'],'hourly_callback_in_new_request'=>$after['hourly_callback'],'backoff'=>$after['backoff'],'lock'=>$after['lock'],'old_rates_preserved'=>$old===$after['rates'],'rates_available'=>$after['settings']['rates_available']);
  if('failure'===$outcome){$dispatch=er_probe_child('dispatch');$report['orphan_dispatch']=array('exit'=>$dispatch['exit'],'provider_requests'=>$dispatch['http'],'events_after'=>$dispatch['after']['events']);}
  $reports[]=$report;
 }
}finally{
 foreach($original as$name=>$value){wp_cache_delete($name,'options');wp_cache_delete('alloptions','options');if(false===$value){delete_option($name);}else{update_option($name,$value);}}
 foreach($transients as$name=>$value){if(false===$value){delete_transient($name);}else{set_transient($name,$value,HOUR_IN_SECONDS);}}
}
echo wp_json_encode(array('wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'reports'=>$reports),JSON_PRETTY_PRINT);
