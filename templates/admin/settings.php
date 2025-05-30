<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <!-- Subscription Types Tab -->
    <h2 class="nav-tab-wrapper">
        <a href="#subscription-types" class="nav-tab nav-tab-active"><?php _e('Subscription Types', 'akadimies'); ?></a>
        <a href="#paypal-settings" class="nav-tab"><?php _e('PayPal Settings', 'akadimies'); ?></a>
        <a href="#database-management" class="nav-tab"><?php _e('Database', 'akadimies'); ?></a>
    </h2>

    <!-- Subscription Types Section -->
    <div id="subscription-types" class="tab-content active">
        <div class="subscription-types-list">
            <h3><?php _e('Manage Subscription Types', 'akadimies'); ?></h3>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'akadimies'); ?></th>
                        <th><?php _e('Price (EUR)', 'akadimies'); ?></th>
                        <th><?php _e('Duration (Days)', 'akadimies'); ?></th>
                        <th><?php _e('Status', 'akadimies'); ?></th>
                        <th><?php _e('Actions', 'akadimies'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subscription_types = get_option('akadimies_subscription_types', array());
                    foreach ($subscription_types as $type_id => $type): 
                    ?>
                        <tr>
                            <td><?php echo esc_html($type['name']); ?></td>
                            <td><?php echo esc_html($type['price']); ?></td>
                            <td><?php echo esc_html($type['duration']); ?></td>
                            <td>
                                <span class="status-<?php echo $type['active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $type['active'] ? __('Active', 'akadimies') : __('Inactive', 'akadimies'); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button edit-type" 
                                        data-id="<?php echo esc_attr($type_id); ?>">
                                    <?php _e('Edit', 'akadimies'); ?>
                                </button>
                                <?php if ($type['active']): ?>
                                    <button type="button" class="button deactivate-type" 
                                            data-id="<?php echo esc_attr($type_id); ?>">
                                        <?php _e('Deactivate', 'akadimies'); ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="button activate-type" 
                                            data-id="<?php echo esc_attr($type_id); ?>">
                                        <?php _e('Activate', 'akadimies'); ?>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="button delete-type" 
                                        data-id="<?php echo esc_attr($type_id); ?>">
                                    <?php _e('Delete', 'akadimies'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button button-primary add-new-type">
                    <?php _e('Add New Subscription Type', 'akadimies'); ?>
                </button>
            </p>
        </div>
    </div>

    <!-- PayPal Settings Section -->
    <div id="paypal-settings" class="tab-content" style="display: none;">
        <form method="post" action="">
            <?php wp_nonce_field('akadimies_settings', 'akadimies_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="paypal_client_id"><?php _e('PayPal Client ID', 'akadimies'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="paypal_client_id" name="paypal_client_id" 
                               value="<?php echo esc_attr($settings['paypal_client_id']); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="paypal_secret"><?php _e('PayPal Secret', 'akadimies'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="paypal_secret" name="paypal_secret" 
                               value="<?php echo esc_attr($settings['paypal_secret']); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('PayPal Mode', 'akadimies'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="paypal_sandbox" value="1" 
                                       <?php checked($settings['paypal_sandbox'], true); ?>>
                                <?php _e('Enable Sandbox Mode', 'akadimies'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Check this to use PayPal Sandbox for testing', 'akadimies'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <!-- Database Management Section -->
    <div id="database-management" class="tab-content" style="display: none;">
        <div class="database-management-section">
            <h3><?php _e('Database Management', 'akadimies'); ?></h3>
            
            <div class="notice notice-warning">
                <p><strong><?php _e('Warning', 'akadimies'); ?>:</strong> <?php _e('These actions cannot be undone!', 'akadimies'); ?></p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('akadimies_database_action', 'database_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="drop_tables" class="button button-delete" 
                           value="<?php _e('Delete All Data', 'akadimies'); ?>"
                           onclick="return confirm('<?php _e('Are you sure you want to delete all subscription data? This cannot be undone!', 'akadimies'); ?>');">
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Subscription Type Modal -->
<div id="subscription-type-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?php _e('Subscription Type', 'akadimies'); ?></h2>
        <form id="subscription-type-form">
            <input type="hidden" name="type_id" id="type_id">
            
            <div class="form-field">
                <label for="type_name"><?php _e('Name', 'akadimies'); ?></label>
                <input type="text" id="type_name" name="name" required>
            </div>

            <div class="form-field">
                <label for="type_price"><?php _e('Price (EUR)', 'akadimies'); ?></label>
                <input type="number" step="0.01" id="type_price" name="price" required>
            </div>

            <div class="form-field">
                <label for="type_duration"><?php _e('Duration (Days)', 'akadimies'); ?></label>
                <input type="number" id="type_duration" name="duration" required>
            </div>

            <div class="form-field">
                <label for="type_description"><?php _e('Description', 'akadimies'); ?></label>
                <textarea id="type_description" name="description" rows="4"></textarea>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="active" value="1" checked>
                    <?php _e('Active', 'akadimies'); ?>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Changes', 'akadimies'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.button-delete {
    background: #dc3232 !important;
    border-color: #dc3232 !important;
    color: white !important;
}
.button-delete:hover {
    background: #cc2929 !important;
    border-color: #cc2929 !important;
}

.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccc;
    border-top: none;
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
    width: 50%;
    max-width: 500px;
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

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-field input[type="text"],
.form-field input[type="number"],
.form-field textarea {
    width: 100%;
    padding: 8px;
}

.status-active {
    color: #46b450;
}

.status-inactive {
    color: #dc3232;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab handling
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update content
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });

    // Modal handling
    $('.close').click(function() {
        $(this).closest('.modal').hide();
    });

    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // Add new subscription type
    $('.add-new-type').click(function() {
        $('#type_id').val('');
        $('#subscription-type-form')[0].reset();
        $('#subscription-type-modal').show();
    });

    // Edit subscription type
    $('.edit-type').click(function() {
        const typeId = $(this).data('id');
        // Load type data and show modal
        // This will be handled by AJAX in the next update
    });

    // Delete subscription type
    $('.delete-type').click(function() {
        if (confirm('Are you sure you want to delete this subscription type?')) {
            const typeId = $(this).data('id');
            // Handle deletion
            // This will be handled by AJAX in the next update
        }
    });

    // Form submission
    $('#subscription-type-form').on('submit', function(e) {
        e.preventDefault();
        // Handle form submission
        // This will be handled by AJAX in the next update
    });
});
</script>
