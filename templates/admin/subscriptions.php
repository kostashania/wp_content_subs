<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Subscription Management', 'akadimies'); ?></h1>
    
    <div class="tablenav top">
        <form method="post" action="">
            <?php wp_nonce_field('akadimies_bulk_action', 'akadimies_nonce'); ?>
            <div class="alignleft actions bulkactions">
                <select name="action">
                    <option value="-1"><?php _e('Bulk Actions', 'akadimies'); ?></option>
                    <option value="activate"><?php _e('Activate', 'akadimies'); ?></option>
                    <option value="deactivate"><?php _e('Deactivate', 'akadimies'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'akadimies'); ?>">
            </div>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" />
                </td>
                <th><?php _e('User', 'akadimies'); ?></th>
                <th><?php _e('Type', 'akadimies'); ?></th>
                <th><?php _e('Status', 'akadimies'); ?></th>
                <th><?php _e('Payment', 'akadimies'); ?></th>
                <th><?php _e('Start Date', 'akadimies'); ?></th>
                <th><?php _e('End Date', 'akadimies'); ?></th>
                <th><?php _e('Actions', 'akadimies'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscriptions as $subscription): ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="subscription[]" value="<?php echo esc_attr($subscription->id); ?>" />
                    </th>
                    <td>
                        <strong><?php echo esc_html($subscription->display_name); ?></strong>
                        <br>
                        <small><?php echo esc_html($subscription->user_email); ?></small>
                    </td>
                    <td><?php echo esc_html($subscription->subscription_type); ?></td>
                    <td>
                        <span class="subscription-status status-<?php echo esc_attr($subscription->status); ?>">
                            <?php echo esc_html(ucfirst($subscription->status)); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($subscription->payment_method): ?>
                            <?php echo esc_html($subscription->payment_method); ?> - 
                            <?php echo esc_html(number_format($subscription->paid_amount, 2)); ?>€
                        <?php else: ?>
                            <span class="no-payment"><?php _e('No payment recorded', 'akadimies'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->start_date))); ?></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->end_date))); ?></td>
                    <td>
                        <button type="button" class="button action-button" 
                                data-subscription-id="<?php echo esc_attr($subscription->id); ?>"
                                data-action="edit">
                            <?php _e('Edit', 'akadimies'); ?>
                        </button>
                        
                        <?php if ($subscription->status === 'pending'): ?>
                            <button type="button" class="button button-primary record-payment-button"
                                    data-subscription-id="<?php echo esc_attr($subscription->id); ?>">
                                <?php _e('Record Payment', 'akadimies'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Subscription Modal -->
<div id="edit-subscription-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?php _e('Edit Subscription', 'akadimies'); ?></h2>
        <form id="edit-subscription-form">
            <input type="hidden" name="subscription_id" id="edit-subscription-id">
            
            <div class="form-field">
                <label for="subscription-status"><?php _e('Status', 'akadimies'); ?></label>
                <select name="status" id="subscription-status">
                    <option value="pending"><?php _e('Pending', 'akadimies'); ?></option>
                    <option value="active"><?php _e('Active', 'akadimies'); ?></option>
                    <option value="expired"><?php _e('Expired', 'akadimies'); ?></option>
                    <option value="cancelled"><?php _e('Cancelled', 'akadimies'); ?></option>
                </select>
            </div>

            <div class="form-field">
                <label for="admin-notes"><?php _e('Admin Notes', 'akadimies'); ?></label>
                <textarea name="admin_notes" id="admin-notes" rows="3"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Changes', 'akadimies'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Record Payment Modal -->
<div id="record-payment-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?php _e('Record Payment', 'akadimies'); ?></h2>
        <form id="record-payment-form">
            <input type="hidden" name="subscription_id" id="payment-subscription-id">
            
            <div class="form-field">
                <label for="payment-method"><?php _e('Payment Method', 'akadimies'); ?></label>
                <select name="payment_method" id="payment-method">
                    <option value="cash"><?php _e('Cash', 'akadimies'); ?></option>
                    <option value="bank_transfer"><?php _e('Bank Transfer', 'akadimies'); ?></option>
                    <option value="other"><?php _e('Other', 'akadimies'); ?></option>
                </select>
            </div>

            <div class="form-field">
                <label for="payment-amount"><?php _e('Amount', 'akadimies'); ?></label>
                <input type="number" step="0.01" name="amount" id="payment-amount" required>
            </div>

            <div class="form-field">
                <label for="payment-notes"><?php _e('Payment Notes', 'akadimies'); ?></label>
                <textarea name="payment_notes" id="payment-notes" rows="3"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Record Payment', 'akadimies'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
