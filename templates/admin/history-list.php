<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php _e('Subscription History', 'akadimies'); ?></h1>

    <?php if (!empty($subscriptions)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User', 'akadimies'); ?></th>
                    <th><?php _e('Type', 'akadimies'); ?></th>
                    <th><?php _e('Status', 'akadimies'); ?></th>
                    <th><?php _e('Initial Amount', 'akadimies'); ?></th>
                    <th><?php _e('Extensions', 'akadimies'); ?></th>
                    <th><?php _e('Total Amount', 'akadimies'); ?></th>
                    <th><?php _e('Start Date', 'akadimies'); ?></th>
                    <th><?php _e('End Date', 'akadimies'); ?></th>
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
                        <td><?php echo esc_html(number_format($subscription->amount, 2)); ?> €</td>
                        <td>
                            <?php if ($subscription->extensions_count > 0): ?>
                                <?php 
                                echo sprintf(
                                    _n('%d extension', '%d extensions', $subscription->extensions_count, 'akadimies'),
                                    $subscription->extensions_count
                                );
                                echo '<br><small>' . sprintf(
                                    __('Total: %s €', 'akadimies'),
                                    number_format($subscription->total_extension_amount, 2)
                                ) . '</small>';
                                ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $total = $subscription->amount + ($subscription->total_extension_amount ?: 0);
                            echo esc_html(number_format($total, 2)); 
                            ?> €
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->start_date))); ?></td>
                        <td><?php echo $subscription->end_date ? esc_html(date_i18n(get_option('date_format'), strtotime($subscription->end_date))) : '-'; ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('subscription', $subscription->id)); ?>" 
                               class="button">
                                <?php _e('View History', 'akadimies'); ?>
                            </a>
                            <?php if ($subscription->status === 'active'): ?>
                                <button type="button" class="button view-details"
                                        data-id="<?php echo esc_attr($subscription->id); ?>">
                                    <?php _e('View Details', 'akadimies'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php _e('No subscription history found.', 'akadimies'); ?></p>
    <?php endif; ?>
</div>

<!-- Subscription Details Modal -->
<div id="subscription-details-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="subscription-details-content"></div>
    </div>
</div>

<style>
.subscription-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-weight: bold;
}

.status-active {
    background: #46b450;
    color: #fff;
}

.status-pending {
    background: #ffb900;
    color: #fff;
}

.status-rejected {
    background: #dc3232;
    color: #fff;
}

.status-expired {
    background: #777;
    color: #fff;
}

.status-cancelled {
    background: #dc3232;
    color: #fff;
}

.modal {
    display: none;
    position: fixed;
    z-index: 999999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 70%;
    position: relative;
    border-radius: 4px;
}

.close {
    position: absolute;
    right: 10px;
    top: 5px;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #000;
}
</style>
