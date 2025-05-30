<?php
if (!defined('ABSPATH')) exit;

class AkadimiesAdmin {
    public function init() {
        // Add menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));

        // Add AJAX handlers
        add_action('wp_ajax_approve_subscription', array($this, 'approve_subscription'));
        add_action('wp_ajax_reject_subscription', array($this, 'reject_subscription'));
        add_action('wp_ajax_get_subscription_details', array($this, 'get_subscription_details'));
        
        // Add subscription type handlers
        add_action('wp_ajax_save_subscription_type', array($this, 'save_subscription_type'));
        add_action('wp_ajax_delete_subscription_type', array($this, 'delete_subscription_type'));
        add_action('wp_ajax_get_subscription_type', array($this, 'get_subscription_type'));
        add_action('wp_ajax_toggle_subscription_type', array($this, 'toggle_subscription_type'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Subscriptions', 
            'Subscriptions', 
            'manage_options', 
            'akadimies-subscriptions',
            array($this, 'render_subscriptions_page'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'akadimies-subscriptions',
            'Settings',
            'Settings',
            'manage_options',
            'akadimies-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('akadimies_options', 'akadimies_subscription_types');
        register_setting('akadimies_options', 'akadimies_paypal_client_id');
        register_setting('akadimies_options', 'akadimies_paypal_secret');
        register_setting('akadimies_options', 'akadimies_paypal_sandbox', array(
            'type' => 'boolean',
            'default' => true
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'akadimies') !== false) {
            wp_enqueue_style('akadimies-admin', AKADIMIES_URL . 'assets/css/admin-style.css');
            wp_enqueue_script('akadimies-admin', AKADIMIES_URL . 'assets/js/admin.js', array('jquery'), AKADIMIES_VERSION, true);
            wp_localize_script('akadimies-admin', 'akadimiesAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('akadimies_admin_nonce'),
                'strings' => array(
                    'confirmDelete' => __('Are you sure you want to delete this subscription type?', 'akadimies'),
                    'confirmDeactivate' => __('Are you sure you want to deactivate this subscription type?', 'akadimies'),
                    'error' => __('An error occurred. Please try again.', 'akadimies')
                )
            ));
        }
    }

    public function render_subscriptions_page() {
        global $wpdb;
        
        $subscriptions = $wpdb->get_results("
            SELECT s.*, u.display_name, u.user_email
            FROM {$wpdb->prefix}akadimies_subscriptions s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            ORDER BY s.created_at DESC
        ");

        include AKADIMIES_PATH . 'templates/admin/dashboard.php';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle database deletion
        if (isset($_POST['drop_tables']) && check_admin_referer('akadimies_database_action', 'database_nonce')) {
            $this->drop_all_tables();
            add_settings_error(
                'akadimies_messages',
                'tables_dropped',
                __('All subscription data has been deleted.', 'akadimies'),
                'updated'
            );
        }

        // Handle regular settings save
        if (isset($_POST['submit']) && check_admin_referer('akadimies_settings', 'akadimies_nonce')) {
            $this->save_settings();
        }

        // Get current settings
        $settings = array(
            'subscription_types' => get_option('akadimies_subscription_types', array()),
            'paypal_client_id' => get_option('akadimies_paypal_client_id', ''),
            'paypal_secret' => get_option('akadimies_paypal_secret', ''),
            'paypal_sandbox' => get_option('akadimies_paypal_sandbox', true)
        );

        include AKADIMIES_PATH . 'templates/admin/settings.php';
    }

    private function save_settings() {
        if (isset($_POST['paypal_client_id'])) {
            update_option('akadimies_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
        }
        if (isset($_POST['paypal_secret'])) {
            update_option('akadimies_paypal_secret', sanitize_text_field($_POST['paypal_secret']));
        }
        if (isset($_POST['paypal_sandbox'])) {
            update_option('akadimies_paypal_sandbox', (bool)$_POST['paypal_sandbox']);
        }

        add_settings_error(
            'akadimies_messages',
            'akadimies_message',
            __('Settings Saved', 'akadimies'),
            'updated'
        );
    }

    public function save_subscription_type() {
        check_ajax_referer('akadimies_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $type_id = isset($_POST['type_id']) ? sanitize_text_field($_POST['type_id']) : '';
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;

        if (empty($name) || $price <= 0 || $duration <= 0) {
            wp_send_json_error(array('message' => 'Invalid data'));
            return;
        }

        $types = get_option('akadimies_subscription_types', array());

        if (empty($type_id)) {
            $type_id = uniqid('type_');
        }

        $types[$type_id] = array(
            'name' => $name,
            'price' => $price,
            'duration' => $duration,
            'description' => $description,
            'active' => $active
        );

        update_option('akadimies_subscription_types', $types);

        wp_send_json_success(array(
            'message' => 'Subscription type saved',
            'type_id' => $type_id
        ));
    }

    public function delete_subscription_type() {
        check_ajax_referer('akadimies_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $type_id = isset($_POST['type_id']) ? sanitize_text_field($_POST['type_id']) : '';

        if (empty($type_id)) {
            wp_send_json_error(array('message' => 'Invalid type ID'));
            return;
        }

        $types = get_option('akadimies_subscription_types', array());

        if (isset($types[$type_id])) {
            unset($types[$type_id]);
            update_option('akadimies_subscription_types', $types);
            wp_send_json_success(array('message' => 'Subscription type deleted'));
        } else {
            wp_send_json_error(array('message' => 'Subscription type not found'));
        }
    }

    public function get_subscription_type() {
        check_ajax_referer('akadimies_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $type_id = isset($_POST['type_id']) ? sanitize_text_field($_POST['type_id']) : '';

        if (empty($type_id)) {
            wp_send_json_error(array('message' => 'Invalid type ID'));
            return;
        }

        $types = get_option('akadimies_subscription_types', array());

        if (isset($types[$type_id])) {
            wp_send_json_success($types[$type_id]);
        } else {
            wp_send_json_error(array('message' => 'Subscription type not found'));
        }
    }

    public function toggle_subscription_type() {
        check_ajax_referer('akadimies_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $type_id = isset($_POST['type_id']) ? sanitize_text_field($_POST['type_id']) : '';
        $active = isset($_POST['active']) ? (bool)$_POST['active'] : false;

        if (empty($type_id)) {
            wp_send_json_error(array('message' => 'Invalid type ID'));
            return;
        }

        $types = get_option('akadimies_subscription_types', array());

        if (isset($types[$type_id])) {
            $types[$type_id]['active'] = $active;
            update_option('akadimies_subscription_types', $types);
            wp_send_json_success(array(
                'message' => $active ? 'Subscription type activated' : 'Subscription type deactivated'
            ));
        } else {
            wp_send_json_error(array('message' => 'Subscription type not found'));
        }
    }

    [Previous methods for approve_subscription, reject_subscription, get_subscription_details, etc. remain the same]
}
