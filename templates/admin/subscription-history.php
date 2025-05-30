<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>
        <?php _e('Subscription History', 'akadimies'); ?>
        <a href="<?php echo admin_url('admin.php?page=akadimies-history'); ?>" class="page-title-action">
            <?php _e('Back to List', 'akadimies'); ?>
        </a>
    </h1>

    <?php if ($subscription): ?>
        <div class="subscription-details">
            <h2><?php _e('Subscription Details', 'akadimies'); ?></h2>
            <table class="widefat">
                <tr>
                    <th><?php _e('User', 'akadimies'); ?></th>
                    <td>
                        <?php echo esc_html($subscription->display_name); ?>
                        <br>
                        <small><?php echo esc_html($subscription->user_email); ?></small>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Type', 'akadimies'); ?></th>
                    <td><?php echo esc_html($subscription->subscription_type); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'akadimies'); ?></th>
                    <td>
                        <span class="subscription-status status-<?php echo esc_attr($subscription->status); ?>">
                            <?php echo esc_html(ucfirst($subscription->status)); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Initial Amount', 'akadimies'); ?></th>
                    <td><?php echo esc_html(number_format($subscription->amount, 2)); ?> €</td>
                </tr>
                <tr>
                    <th><?php _e('Start Date', 'akadimies'); ?></th>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($subscription->start_date))); ?></td>
                </tr>
                <tr>
                    <th><?php _e('End Date', 'akadimies'); ?></th>
                    <td><?php echo $subscription->end_date ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($subscription->end_date))) : '-'; ?></td>
                </tr>
                <?php if (!empty($subscription->admin_notes)): ?>
                    <tr>
                        <th><?php _e('Admin Notes', 'akadimies'); ?></th>
                        <td><?php echo nl2br(esc_html($subscription->admin_notes)); ?></td>
                    </tr>
                <?php endif; ?>
            </table>

            <?php if (!empty($extensions)): ?>
                <h2><?php _e('Extension History', 'akadimies'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'akadimies'); ?></th>
                            <th><?php _e('Duration Added', 'akadimies'); ?></th>
                            <th><?php _e('Amount', 'akadimies'); ?></th>
                            <th><?php _e('Payment Method', 'akadimies'); ?></th>
                            <th><?php _e('Transaction ID', 'akadimies'); ?></th>
                            <th><?php _e('Previous End Date', 'akadimies'); ?></th>
                            <th><?php _e('New End Date', 'akadimies'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_duration = 0;
                        $total_amount = $subscription->amount;
                        foreach ($extensions as $extension): 
                            $total_duration += $extension->duration;
                            $total_amount += $extension->amount;
                        ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($extension->created_at))); ?></td>
                                <td><?php echo esc_html($extension->duration); ?> <?php _e('days', 'akadimies'); ?></td>
                                <td><?php echo esc_html(number_format($extension->amount, 2)); ?> €</td>
                                <td><?php echo esc_html(ucfirst($extension->payment_method ?: 'N/A')); ?></td>
                                <td><?php echo $extension->transaction_id ? esc_html($extension->transaction_id) : '-'; ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($extension->previous_end_date))); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($extension->new_end_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2"><?php _e('Totals:', 'akadimies'); ?></th>
                            <td><strong><?php echo esc_html(number_format($total_amount, 2)); ?> €</strong></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="subscription-summary">
                    <h3><?php _e('Summary', 'akadimies'); ?></h3>
                    <p>
                        <?php
                        printf(
                            __('This subscription has been extended %d times, adding %d days in total. The initial amount was %s € and extensions total %s €, making a total payment of %s €.', 'akadimies'),
                            count($extensions),
                            $total_duration,
                            number_format($subscription->amount, 2),
                            number_format($total_amount - $subscription->amount, 2),
                            number_format($total_amount, 2)
                        );
                        ?>
                    </p>
                </div>
            <?php else: ?>
                <p><?php _e('No extensions found for this subscription.', 'akadimies'); ?></p>
            <?php endif; ?>

            <?php if ($subscription->status === 'active'): ?>
                <div class="subscription-actions">
                    <h3><?php _e('Add Extension', 'akadimies'); ?></h3>
                    <form id="extend-subscription-form" class="extend-subscription-form">
                        <input type="hidden" name="subscription_id" value="<?php echo esc_attr($subscription->id); ?>">
                        <input type="hidden" name="action" value="extend_subscription">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('akadimies_admin_nonce'); ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="extension_duration"><?php _e('Duration (days)', 'akadimies'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="extension_duration" name="duration" min="1" value="30" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="extension_amount"><?php _e('Amount (€)', 'akadimies'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="extension_amount" name="amount" min="0.01" step="0.01" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="payment_method"><?php _e('Payment Method', 'akadimies'); ?></label>
                                </th>
                                <td>
                                    <select id="payment_method" name="payment_method">
                                        <option value="cash"><?php _e('Cash', 'akadimies'); ?></option>
                                        <option value="bank_transfer"><?php _e('Bank Transfer', 'akadimies'); ?></option>
                                        <option value="paypal"><?php _e('PayPal', 'akadimies'); ?></option>
                                        <option value="other"><?php _e('Other', 'akadimies'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="extension_notes"><?php _e('Notes', 'akadimies'); ?></label>
                                </th>
                                <td>
                                    <textarea id="extension_notes" name="notes" rows="3"></textarea>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Add Extension', 'akadimies'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="notice notice-error">
            <p><?php _e('Subscription not found.', 'akadimies'); ?></p>
        </div>
    <?php endif; ?>
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

.subscription-summary {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border-left: 4px solid #46b450;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.subscription-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.extend-subscription-form {
    max-width: 600px;
}
</style>
