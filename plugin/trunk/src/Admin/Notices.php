<?php 

namespace Dejurin\ExchangeRates\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use Dejurin\ExchangeRates\Plugin;

    class Notices {

        private static $_instance;
        private $admin_notices;
        const TYPES = 'error,warning,info,success';

        private function __construct() {
            $this->admin_notices = new \stdClass();
            foreach ( explode( ',', self::TYPES ) as $type ) {
                $this->admin_notices->{$type} = [];
            }
            add_action( 'admin_init', [$this, 'action_admin_init'] );
            add_action( 'admin_notices', [$this, 'action_admin_notices'] );
            add_action( 'admin_enqueue_scripts', [$this, 'action_admin_enqueue_scripts'] );
        }

        public static function get_instance() {
            if ( ! ( self::$_instance instanceof self ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function action_admin_init() {
            $dismiss_option = isset($_GET[Plugin::PLUGIN_SLUG.'_dismiss']) ? sanitize_key(wp_unslash($_GET[Plugin::PLUGIN_SLUG.'_dismiss'])) : '';
            if ( '' !== $dismiss_option ) {
                if (!current_user_can('manage_options')) {
                    wp_die(esc_html__('Access denied.', 'exchange-rates'), 403);
                }

                $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
                if (!wp_verify_nonce($nonce, Plugin::PLUGIN_SLUG.'_dismiss_'.$dismiss_option)) {
                    wp_die(esc_html__('Invalid request.', 'exchange-rates'), 403);
                }

                update_option(Plugin::PLUGIN_SLUG."_dismissed_".$dismiss_option, true);
                if (get_option(Plugin::PLUGIN_SLUG."_dismissed_".$dismiss_option) == 1) {
                    esc_html_e('Successful!', 'exchange-rates');
                    wp_die();
                }
            }
        }

        public function action_admin_enqueue_scripts() {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script(
				Plugin::PLUGIN_SLUG . '-notices',
				plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path) . 'assets/js/admin/notices.js',
				['jquery'],
				Plugin::VERSION,
				true
			);
        }

        public function action_admin_notices() {
            foreach (explode(',', self::TYPES) as $type) {
                foreach ($this->admin_notices->{$type} as $admin_notice) {
                    $dismiss_url = add_query_arg([Plugin::PLUGIN_SLUG . '_dismiss' => $admin_notice->dismiss_option], admin_url());
                    $dismiss_url = wp_nonce_url($dismiss_url, Plugin::PLUGIN_SLUG.'_dismiss_'.$admin_notice->dismiss_option);
                    $screen = get_current_screen();
                    if (!get_option(Plugin::PLUGIN_SLUG . "_dismissed_{$admin_notice->dismiss_option}")) {
                        ?>
                        <div class="notice is-dismissible <?php echo esc_attr(Plugin::PLUGIN_SLUG); ?>-notice notice-<?php echo esc_attr($type);
                            if ($admin_notice->dismiss_option) {
                                echo ' is-dismissible" data-dismiss-url="' . esc_url($dismiss_url);
                            } ?>">
                            <div class="<?php echo esc_attr(Plugin::PLUGIN_SLUG); ?>-rate-notice-container">
                                <div class="logo-img">
                                    <img src="<?php echo esc_url(plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path) . 'assets/img/bank.svg'); ?>" alt="<?php echo esc_attr(Plugin::NAME); ?>" style="width:96px" />
                                </div>
                                <div>
                                    <h2>🥰 <?php esc_html_e('Please rate our free', 'exchange-rates');
                                    ?>&laquo;<?php echo esc_attr(Plugin::NAME); ?>&raquo;</h2>
                                    <hr>
                                    <p><?php esc_html_e('Your valuable feedback will help us improve!', 'exchange-rates'); ?><br><?php esc_html_e('It will only take a few minutes', 'exchange-rates'); ?>: <a href="https://wordpress.org/support/plugin/<?php echo esc_attr(Plugin::PLUGIN_SLUG); ?>/reviews/#new-post" rel="noopener" target="_blank"><?php esc_html_e('Rate it now', 'exchange-rates'); ?></a> 👍</p>
                                    <p><a href="https://wordpress.org/support/plugin/<?php echo esc_attr(Plugin::PLUGIN_SLUG); ?>/reviews/#new-post" rel="noopener" target="_blank"><img src="<?php echo esc_url(plugin_dir_url($GLOBALS['dejurin_exchange_rates']->plugin_path) . 'assets/img/stars.png'); ?>" alt="<?php esc_attr_e('Rating', 'exchange-rates'); ?>"></a></p>
                                </div>
                            </div>
                        </div>
                        <style>
                            .exchange-rates-rate-notice-container {
                                display: flex;
                                padding: 10px 0;
                            }
                            .exchange-rates-rate-notice-container .logo-img {
                                margin-right: 15px;
                            }
                            .exchange-rates-rate-notice-container h2 {
                                margin: 0;
                            }
                            .exchange-rates-rate-notice-container p {
                                padding: 0;
                                margin: 0;
                            }
                        </style>
                        <?php
                    }
                }
            }
        }        

        public function error( $message, $dismiss_option = false ) {
            $this->notice( 'error', $message, $dismiss_option );
        }

        public function warning( $message, $dismiss_option = false ) {
            $this->notice( 'warning', $message, $dismiss_option );
        }

        public function success( $message, $dismiss_option = false ) {
            $this->notice( 'success', $message, $dismiss_option );
        }

        public function info( $message, $dismiss_option = false ) {
            $this->notice( 'info', $message, $dismiss_option );
        }

        private function notice( $type, $message, $dismiss_option ) {
            $notice = new \stdClass();
            $notice->message = $message;
            $notice->dismiss_option = $dismiss_option;
            $this->admin_notices->{$type}[] = $notice;
        }
    }
