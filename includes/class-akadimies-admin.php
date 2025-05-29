<?php
if (!defined('ABSPATH')) exit;

class AkadimiesAdmin {
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Akadimies Subscriptions',
            'Subscriptions',
            'manage_options',
            'akadimies-subscriptions',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'akadimies-subscriptions',
            'Settings',
            'Settings',
            'manage_options',
            'akadimies-settings',
            array($this, 'render_settings')
        );
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $subscriptions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
            ORDER BY created_at DESC 
            LIMIT 10"
        );

        require_once AKADIMIES_PATH . 'templates/admin/dashboard.php';
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['akadimies_settings_nonce']) && 
            wp_verify_nonce($_POST['akadimies_settings_nonce'], 'akadimies_save_settings')) {
            $this->save_settings();
        }

        $settings = array(
            'player_price' => get_option('akadimies_player_price', '29.99'),
            'coach_price' => get_option('akadimies_coach_price', '49.99'),
            'sponsor_price' => get_option('akadimies_sponsor_price', '99.99'),
            'paypal_client_id' => get_option('akadimies_paypal_client_id', ''),
            'paypal_secret' => get_option('akadimies_paypal_secret', ''),
            'paypal_sandbox' => get_option('akadimies_paypal_sandbox', '1')
        );

        require_once AKADIMIES_PATH . 'templates/admin/settings.php';
    }

    private function save_settings() {
        $settings = array(
            'akadimies_player_price' => 'floatval',
            'akadimies_coach_price' => 'floatval',
            'akadimies_sponsor_price' => 'floatval',
            'akadimies_paypal_client_id' => 'sanitize_text_field',
            'akadimies_paypal_secret' => 'sanitize_text_field',
            'akadimies_paypal_sandbox' => 'intval'
        );

        foreach ($settings as $option => $sanitize_callback) {
            if (isset($_POST[$option])) {
                update_option($option, $sanitize_callback($_POST[$option]));
            }
        }

        add_settings_error(
            'akadimies_messages',
            'akadimies_message',
            __('Settings Saved', 'akadimies'),
            'updated'
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'akadimies') === false) {
            return;
        }

        wp_enqueue_style(
            'akadimies-admin-style',
            AKADIMIES_URL . 'assets/css/admin-style.css',
            array(),
            AKADIMIES_VERSION
        );

        wp_enqueue_script(
            'akadimies-admin-script',
            AKADIMIES_URL . 'assets/js/admin.js',
            array('jquery'),
            AKADIMIES_VERSION,
            true
        );
    }
}
