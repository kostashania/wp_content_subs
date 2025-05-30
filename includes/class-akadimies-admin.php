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

    private function drop_all_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'akadimies_subscriptions',
            $wpdb->prefix . 'akadimies_payments'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'akadimies_%'");

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

    global $wpdb;

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
        // Get the subscription we want to approve
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions WHERE id = %d",
            $subscription_id
        ));

        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        // Check for existing active subscription
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
            WHERE user_id = %d 
            AND subscription_type = %s 
            AND status = 'active'
            AND id != %d",
            $subscription->user_id,
            $subscription->subscription_type,
            $subscription_id
        ));

        if ($existing) {
            // Calculate new end date for existing subscription
            $new_end_date = $existing->end_date ? 
                date('Y-m-d H:i:s', strtotime($existing->end_date . ' + 30 days')) :
                date('Y-m-d H:i:s', strtotime('+30 days'));

            // Update existing subscription
            $wpdb->update(
                $wpdb->prefix . 'akadimies_subscriptions',
                array(
                    'end_date' => $new_end_date,
                    'amount' => $existing->amount + $subscription->amount,
                    'admin_notes' => sprintf(
                        "Previous notes: %s\n\nMerged with subscription #%d on %s. Added amount: €%s",
                        $existing->admin_notes,
                        $subscription_id,
                        current_time('mysql'),
                        $subscription->amount
                    ),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing->id)
            );

            // Update the new subscription to mark it as merged
            $wpdb->update(
                $wpdb->prefix . 'akadimies_subscriptions',
                array(
                    'status' => 'merged',
                    'admin_notes' => sprintf(
                        "Merged into subscription #%d on %s",
                        $existing->id,
                        current_time('mysql')
                    ),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $subscription_id)
            );
        } else {
            // No existing active subscription, just activate this one
            $wpdb->update(
                $wpdb->prefix . 'akadimies_subscriptions',
                array(
                    'status' => 'active',
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $subscription_id)
            );
        }

        $wpdb->query('COMMIT');
        wp_send_json_success(array('message' => 'Subscription processed successfully'));

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
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

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'akadimies_subscriptions',
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

        global $wpdb;
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.display_name, u.user_email 
            FROM {$wpdb->prefix}akadimies_subscriptions s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.id = %d",
            $subscription_id
        ));

        if ($subscription) {
            ob_start();
            ?>
            <table class="widefat">
                <tr>
                    <th><?php _e('User', 'akadimies'); ?></th>
                    <td><?php echo esc_html($subscription->display_name); ?> (<?php echo esc_html($subscription->user_email); ?>)</td>
                </tr>
                <tr>
                    <th><?php _e('Type', 'akadimies'); ?></th>
                    <td><?php echo esc_html($subscription->subscription_type); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'akadimies'); ?></th>
                    <td><?php echo esc_html($subscription->status); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Start Date', 'akadimies'); ?></th>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->start_date))); ?></td>
                </tr>
                <tr>
                    <th><?php _e('End Date', 'akadimies'); ?></th>
                    <td><?php echo $subscription->end_date ? esc_html(date_i18n(get_option('date_format'), strtotime($subscription->end_date))) : ''; ?></td>
                </tr>
                <?php if (!empty($subscription->admin_notes)): ?>
                <tr>
                    <th><?php _e('Notes', 'akadimies'); ?></th>
                    <td><?php echo esc_html($subscription->admin_notes); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('message' => 'Subscription not found'));
        }

        wp_die();
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
    public function render_subscription_history($subscription_id) {
    global $wpdb;
    
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, u.display_name, u.user_email 
        FROM {$wpdb->prefix}akadimies_subscriptions s
        LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
        WHERE s.id = %d",
        $subscription_id
    ));

    if (!$subscription) {
        return;
    }

    $extensions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}akadimies_subscription_extensions 
        WHERE subscription_id = %d 
        ORDER BY created_at ASC",
        $subscription_id
    ));

    include AKADIMIES_PATH . 'templates/admin/subscription-history.php';
    }
}