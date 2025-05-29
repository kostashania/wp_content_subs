<?php
if (!defined('ABSPATH')) exit;

class AkadimiesAdmin {
    public function init() {
        // Add menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));

        // Add AJAX handlers
        add_action('wp_ajax_update_subscription_status', array($this, 'ajax_update_subscription_status'));
        add_action('wp_ajax_get_subscription_details', array($this, 'ajax_get_subscription_details'));
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
        register_setting('akadimies_options', 'akadimies_player_price');
        register_setting('akadimies_options', 'akadimies_coach_price');
        register_setting('akadimies_options', 'akadimies_sponsor_price');
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
                'nonce' => wp_create_nonce('akadimies-admin')
            ));
        }
    }

    public function render_subscriptions_page() {
        global $wpdb;
        
        // Get subscriptions with user data only
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

        // Save settings if form was submitted
        if (isset($_POST['submit']) && check_admin_referer('akadimies_settings')) {
            $this->save_settings();
        }

        // Get current settings
        $settings = array(
            'player_price' => get_option('akadimies_player_price', '29.99'),
            'coach_price' => get_option('akadimies_coach_price', '49.99'),
            'sponsor_price' => get_option('akadimies_sponsor_price', '99.99'),
            'paypal_client_id' => get_option('akadimies_paypal_client_id', ''),
            'paypal_secret' => get_option('akadimies_paypal_secret', ''),
            'paypal_sandbox' => get_option('akadimies_paypal_sandbox', true)
        );

        include AKADIMIES_PATH . 'templates/admin/settings.php';
    }

    private function save_settings() {
        if (isset($_POST['player_price'])) {
            update_option('akadimies_player_price', sanitize_text_field($_POST['player_price']));
        }
        if (isset($_POST['coach_price'])) {
            update_option('akadimies_coach_price', sanitize_text_field($_POST['coach_price']));
        }
        if (isset($_POST['sponsor_price'])) {
            update_option('akadimies_sponsor_price', sanitize_text_field($_POST['sponsor_price']));
        }
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

    public function ajax_update_subscription_status() {
        check_ajax_referer('akadimies-admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $subscription_id = intval($_POST['subscription_id']);
        $status = sanitize_text_field($_POST['status']);
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'akadimies_subscriptions',
            array(
                'status' => $status,
                'admin_notes' => $notes,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscription_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($updated) {
            // Send email notification to user
            $subscription = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, u.user_email, u.display_name 
                FROM {$wpdb->prefix}akadimies_subscriptions s
                LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                WHERE s.id = %d",
                $subscription_id
            ));

            if ($subscription) {
                $this->send_status_notification($subscription, $status, $notes);
            }

            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Failed to update subscription'));
        }
    }

    public function ajax_get_subscription_details() {
        check_ajax_referer('akadimies-admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $subscription_id = intval($_POST['subscription_id']);

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
    }

    private function send_status_notification($subscription, $status, $notes = '') {
        $subject = sprintf(__('Your subscription status has been updated - %s', 'akadimies'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Dear %s,

Your subscription has been %s.

%s

Thank you for using our service.

Best regards,
%s', 'akadimies'),
            $subscription->display_name,
            $status,
            $notes ? "\nNotes: " . $notes : '',
            get_bloginfo('name')
        );

        wp_mail($subscription->user_email, $subject, $message);
    }
}
