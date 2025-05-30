<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-details">
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
        <h3><?php _e('Subscription Extensions', 'akadimies'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Date', 'akadimies'); ?></th>
                    <th><?php _e('Duration Added', 'akadimies'); ?></th>
                    <th><?php _e('Amount', 'akadimies'); ?></th>
                    <th><?php _e('Payment Method', 'akadimies'); ?></th>
                    <th><?php _e('Previous End Date', 'akadimies'); ?></th>
                    <th><?php _e('New End Date', 'akadimies'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($extensions as $extension): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($extension->created_at))); ?></td>
                        <td><?php echo esc_html($extension->duration); ?> <?php _e('days', 'akadimies'); ?></td>
                        <td><?php echo esc_html(number_format($extension->amount, 2)); ?> €</td>
                        <td>
                            <?php 
                            echo esc_html($extension->payment_method ?: 'N/A');
                            if ($extension->transaction_id) {
                                echo ' (' . esc_html($extension->transaction_id) . ')';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($extension->previous_end_date))); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($extension->new_end_date))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($subscription->status === 'active'): ?>
        <div class="subscription-actions">
            <h3><?php _e('Extend Subscription', 'akadimies'); ?></h3>
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
                        <?php _e('Extend Subscription', 'akadimies'); ?>
                    </button>
                </p>
            </form>
        </div>
    <?php endif; ?>
</div>
