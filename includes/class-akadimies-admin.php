<?php
if (!defined('ABSPATH')) exit;

class AkadimiesAdmin {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function init() {
        // Add menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));

        // Add AJAX handlers
        add_action('wp_ajax_approve_subscription', array($this, 'approve_subscription'));
        add_action('wp_ajax_reject_subscription', array($this, 'reject_subscription'));
        add_action('wp_ajax_get_subscription_details', array($this, 'get_subscription_details'));
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
                'nonce' => wp_create_nonce('akadimies_admin_nonce')
            ));
        }
    }

    public function render_subscriptions_page() {
        $subscriptions = $this->wpdb->get_results("
            SELECT s.*, u.display_name, u.user_email
            FROM {$this->wpdb->prefix}akadimies_subscriptions s
            LEFT JOIN {$this->wpdb->users} u ON s.user_id = u.ID
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

    private function drop_all_tables() {
        $tables = array(
            $this->wpdb->prefix . 'akadimies_subscriptions',
            $this->wpdb->prefix . 'akadimies_subscription_extensions',
            $this->wpdb->prefix . 'akadimies_payments'
        );

        foreach ($tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }

        $this->wpdb->query("DELETE FROM {$this->wpdb->options} WHERE option_name LIKE 'akadimies_%'");

        wp_clear_scheduled_hook('akadimies_daily_subscription_check');
        wp_clear_scheduled_hook('akadimies_cleanup_expired_subscriptions');

        error_log('Akadimies tables dropped by admin');

        require_once AKADIMIES_PATH . 'includes/class-akadimies-database.php';
        $database = new AkadimiesDatabase();
        $database->install();
    }

    public function approve_subscription() {
        check_ajax_referer('akadimies_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            wp_die();
        }

        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;

        if (!$subscription_id) {
            wp_send_json_error(array('message' => 'Invalid subscription ID'));
            wp_die();
        }

        // Start transaction
        $this->wpdb->query('START TRANSACTION');

        try {
            // Get the subscription we want to approve
            $subscription = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}akadimies_subscriptions WHERE id = %d",
                $subscription_id
            ));

            if (!$subscription) {
                throw new Exception('Subscription not found');
            }

            // Check for existing active subscription
            $existing = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}akadimies_subscriptions 
                WHERE user_id = %d 
                AND subscription_type = %s 
                AND status = 'active'
                AND id != %d",
                $subscription->user_id,
                $subscription->subscription_type,
                $subscription_id
            ));

            if ($existing) {
                // Calculate days to add (default 30 if not specified)
                $days_to_add = 30;

                // Calculate new end date for existing subscription
                if ($existing->end_date) {
                    $new_end_date = date('Y-m-d H:i:s', strtotime($existing->end_date . ' + ' . $days_to_add . ' days'));
                } else {
                    $new_end_date = date('Y-m-d H:i:s', strtotime('+' . $days_to_add . ' days'));
                }

                // Update existing subscription
                $this->wpdb->update(
                    $this->wpdb->prefix . 'akadimies_subscriptions',
                    array(
                        'end_date' => $new_end_date,
                        'amount' => $existing->amount + $subscription->amount,
                        'admin_notes' => sprintf(
                            "Previous notes: %s\n\nMerged with subscription #%d on %s. Added amount: €%s, Added days: %d\nNew end date: %s",
                            $existing->admin_notes ? $existing->admin_notes . "\n" : '',
                            $subscription_id,
                            current_time('mysql'),
                            $subscription->amount,
                            $days_to_add,
                            $new_end_date
                        ),
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $existing->id)
                );

                // Create extension record
                $this->wpdb->insert(
                    $this->wpdb->prefix . 'akadimies_subscription_extensions',
                    array(
                        'subscription_id' => $existing->id,
                        'amount' => $subscription->amount,
                        'duration' => $days_to_add,
                        'previous_end_date' => $existing->end_date,
                        'new_end_date' => $new_end_date,
                        'created_at' => current_time('mysql')
                    )
                );

                // Update the new subscription to mark it as merged
                $this->wpdb->update(
                    $this->wpdb->prefix . 'akadimies_subscriptions',
                    array(
                        'status' => 'merged',
                        'admin_notes' => sprintf(
                            "Merged into subscription #%d on %s. Added %d days to existing subscription.",
                            $existing->id,
                            current_time('mysql'),
                            $days_to_add
                        ),
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $subscription_id)
                );
            } else {
                // No existing active subscription, activate this one with 30 days
                $end_date = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $this->wpdb->update(
                    $this->wpdb->prefix . 'akadimies_subscriptions',
                    array(
                        'status' => 'active',
                        'end_date' => $end_date,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $subscription_id)
                );
            }

            $this->wpdb->query('COMMIT');
            wp_send_json_success(array('message' => 'Subscription processed successfully'));

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }

        wp_die();
    }

    public function reject_subscription() {
        check_ajax_referer('akadimies_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            wp_die();
        }

        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (!$subscription_id) {
            wp_send_json_error(array('message' => 'Invalid subscription ID'));
            wp_die();
        }

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'akadimies_subscriptions',
            array(
                'status' => 'rejected',
                'admin_notes' => $notes,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscription_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Subscription rejected'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update subscription'));
        }

        wp_die();
    }

    public function get_subscription_details() {
        check_ajax_referer('akadimies_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            wp_die();
        }

        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;

        if (!$subscription_id) {
            wp_send_json_error(array('message' => 'Invalid subscription ID'));
            wp_die();
        }

        $subscription = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT s.*, u.display_name, u.user_email 
            FROM {$this->wpdb->prefix}akadimies_subscriptions s
            LEFT JOIN {$this->wpdb->users} u ON s.user_id = u.ID
            WHERE s.id = %d",
            $subscription_id
        ));

        if ($subscription) {
            // Get extensions
            $extensions = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT e.*, p.payment_method, p.transaction_id
                FROM {$this->wpdb->prefix}akadimies_subscription_extensions e
                LEFT JOIN {$this->wpdb->prefix}akadimies_payments p ON e.payment_id = p.id
                WHERE e.subscription_id = %d
                ORDER BY e.created_at DESC",
                $subscription_id
            ));

            ob_start();
            include AKADIMIES_PATH . 'templates/admin/subscription-details.php';
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('message' => 'Subscription not found'));
        }

        wp_die();
    }
}
