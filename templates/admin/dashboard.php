<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <?php 
    // Add this at the top of the file
    wp_nonce_field('update_subscription_status', 'subscription_nonce'); 
    ?>

    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="akadimies-dashboard">
        <div class="dashboard-widget">
            <h2><?php _e('Recent Subscriptions', 'akadimies'); ?></h2>
            
            <?php if (!empty($subscriptions)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'akadimies'); ?></th>
                            <th><?php _e('Type', 'akadimies'); ?></th>
                            <th><?php _e('Status', 'akadimies'); ?></th>
                            <th><?php _e('Date', 'akadimies'); ?></th>
                            <th><?php _e('Actions', 'akadimies'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <tr>
                                <td>
                                    <?php 
                                    echo esc_html($subscription->display_name ?: __('Unknown User', 'akadimies'));
                                    echo '<br><small>' . esc_html($subscription->user_email) . '</small>';
                                    ?>
                                </td>
                                <td><?php echo esc_html($subscription->subscription_type); ?></td>
                                <td>
                                    <span class="subscription-status status-<?php echo esc_attr($subscription->status); ?>">
                                        <?php echo esc_html(ucfirst($subscription->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->created_at))); ?></td>
                                <td>
                                    <?php if ($subscription->status === 'pending'): ?>
                                        <button type="button" class="button button-primary approve-subscription" 
                                                data-id="<?php echo esc_attr($subscription->id); ?>">
                                            <?php _e('Approve', 'akadimies'); ?>
                                        </button>
                                        <button type="button" class="button reject-subscription"
                                                data-id="<?php echo esc_attr($subscription->id); ?>">
                                            <?php _e('Reject', 'akadimies'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="button view-details"
                                            data-id="<?php echo esc_attr($subscription->id); ?>">
                                        <?php _e('View Details', 'akadimies'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No subscriptions found.', 'akadimies'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Subscription Details Modal -->
<div id="subscription-details-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?php _e('Subscription Details', 'akadimies'); ?></h2>
        <div id="subscription-details-content"></div>
    </div>
</div>
