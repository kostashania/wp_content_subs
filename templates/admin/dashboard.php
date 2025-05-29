<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <tr>
                                <td>
                                    <?php 
                                    echo esc_html($subscription->display_name ?: __('Unknown User', 'akadimies'));
                                    ?>
                                </td>
                                <td><?php echo esc_html($subscription->subscription_type); ?></td>
                                <td><?php echo esc_html($subscription->status); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->created_at))); ?></td>
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
