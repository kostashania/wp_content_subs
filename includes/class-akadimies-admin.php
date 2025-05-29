// Add these methods to your AkadimiesAdmin class

public function __construct() {
    add_action('wp_ajax_update_subscription_status', array($this, 'ajax_update_subscription_status'));
    add_action('wp_ajax_get_subscription_details', array($this, 'ajax_get_subscription_details'));
}

public function ajax_update_subscription_status() {
    // Verify nonce
    check_ajax_referer('approve-subscription-' . $_POST['subscription_id']);

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
                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->end_date))); ?></td>
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
