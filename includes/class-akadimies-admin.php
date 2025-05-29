<?php
if (!defined('ABSPATH')) exit;

class AkadimiesAdmin {
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_update_subscription_status', array($this, 'update_subscription_status'));
        add_action('wp_ajax_record_manual_payment', array($this, 'record_manual_payment'));
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
            'Payment History',
            'Payment History',
            'manage_options',
            'akadimies-payments',
            array($this, 'render_payments_page')
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

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'akadimies') !== false) {
            wp_enqueue_style('akadimies-admin', AKADIMIES_URL . 'assets/css/admin-style.css');
            wp_enqueue_script('akadimies-admin', AKADIMIES_URL . 'assets/js/admin.js', array('jquery'), AKADIMIES_VERSION, true);
            wp_localize_script('akadimies-admin', 'akadimiesAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('akadimies-admin')
            ));
        }
    }

    public function render_subscriptions_page() {
        global $wpdb;
        
        // Handle bulk actions
        if (isset($_POST['action']) && check_admin_referer('akadimies_bulk_action', 'akadimies_nonce')) {
            $this->handle_bulk_actions();
        }

        // Get subscriptions with user data
        $subscriptions = $wpdb->get_results("
            SELECT s.*, u.display_name, u.user_email,
                   p.payment_method, p.amount as paid_amount, p.payment_date
            FROM {$wpdb->prefix}akadimies_subscriptions s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}akadimies_payments p ON s.id = p.subscription_id
            ORDER BY s.created_at DESC
        ");

        include AKADIMIES_PATH . 'templates/admin/subscriptions.php';
    }

    public function render_payments_page() {
        global $wpdb;
        
        $payments = $wpdb->get_results("
            SELECT p.*, s.subscription_type, u.display_name, u.user_email
            FROM {$wpdb->prefix}akadimies_payments p
            LEFT JOIN {$wpdb->prefix}akadimies_subscriptions s ON p.subscription_id = s.id
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            ORDER BY p.payment_date DESC
        ");

        include AKADIMIES_PATH . 'templates/admin/payments.php';
    }

    public function update_subscription_status() {
        check_ajax_referer('akadimies-admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $subscription_id = intval($_POST['subscription_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $admin_notes = sanitize_textarea_field($_POST['admin_notes']);

        global $wpdb;
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'akadimies_subscriptions',
            array(
                'status' => $new_status,
                'admin_notes' => $admin_notes,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscription_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($updated) {
            // Send notification to user
            $this->notify_user_status_change($subscription_id, $new_status);
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Update failed');
        }
    }

    public function record_manual_payment() {
        check_ajax_referer('akadimies-admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $subscription_id = intval($_POST['subscription_id']);
        $amount = floatval($_POST['amount']);
        $payment_method = sanitize_text_field($_POST['payment_method']); // cash, bank_transfer, etc.
        $payment_notes = sanitize_textarea_field($_POST['payment_notes']);

        global $wpdb;

        // Record payment
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'akadimies_payments',
            array(
                'subscription_id' => $subscription_id,
                'payment_method' => $payment_method,
                'amount' => $amount,
                'payment_date' => current_time('mysql'),
                'status' => 'completed',
                'notes' => $payment_notes
            ),
            array('%d', '%s', '%f', '%s', '%s', '%s')
        );

        if ($inserted) {
            // Update subscription status
            $wpdb->update(
                $wpdb->prefix . 'akadimies_subscriptions',
                array(
                    'status' => 'active',
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $subscription_id)
            );

            // Generate receipt
            $receipt_id = $this->generate_receipt($wpdb->insert_id);
            
            wp_send_json_success(array(
                'message' => 'Payment recorded',
                'receipt_id' => $receipt_id
            ));
        } else {
            wp_send_json_error('Failed to record payment');
        }
    }

    private function generate_receipt($payment_id) {
        // Generate unique receipt number
        $receipt_number = 'RCP-' . date('Ymd') . '-' . $payment_id;
        
        // Update payment record with receipt number
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'akadimies_payments',
            array('receipt_number' => $receipt_number),
            array('id' => $payment_id)
        );

        return $receipt_number;
    }

    private function notify_user_status_change($subscription_id, $new_status) {
        global $wpdb;
        
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.user_email, u.display_name 
            FROM {$wpdb->prefix}akadimies_subscriptions s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.id = %d",
            $subscription_id
        ));

        if ($subscription) {
            $subject = sprintf(__('Your subscription status has been updated - %s', 'akadimies'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Dear %s,\n\nYour subscription status has been updated to: %s\n\nThank you for using our service.\n\nBest regards,\n%s', 'akadimies'),
                $subscription->display_name,
                $new_status,
                get_bloginfo('name')
            );

            wp_mail($subscription->user_email, $subject, $message);
        }
    }

    private function handle_bulk_actions() {
        $action = $_POST['action'];
        $subscriptions = isset($_POST['subscription']) ? (array)$_POST['subscription'] : array();

        if (empty($subscriptions)) {
            return;
        }

        global $wpdb;

        switch ($action) {
            case 'activate':
                foreach ($subscriptions as $subscription_id) {
                    $wpdb->update(
                        $wpdb->prefix . 'akadimies_subscriptions',
                        array('status' => 'active'),
                        array('id' => intval($subscription_id))
                    );
                }
                break;

            case 'deactivate':
                foreach ($subscriptions as $subscription_id) {
                    $wpdb->update(
                        $wpdb->prefix . 'akadimies_subscriptions',
                        array('status' => 'inactive'),
                        array('id' => intval($subscription_id))
                    );
                }
                break;
        }
    }
}
