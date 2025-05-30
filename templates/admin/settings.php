<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <form method="post" action="">
        <?php wp_nonce_field('akadimies_settings', 'akadimies_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="player_price"><?php _e('Player Subscription Price', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="player_price" name="player_price" 
                           value="<?php echo esc_attr($settings['player_price']); ?>" class="regular-text">
                    <p class="description"><?php _e('Price in EUR', 'akadimies'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="coach_price"><?php _e('Coach Subscription Price', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="coach_price" name="coach_price" 
                           value="<?php echo esc_attr($settings['coach_price']); ?>" class="regular-text">
                    <p class="description"><?php _e('Price in EUR', 'akadimies'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="sponsor_price"><?php _e('Sponsor Subscription Price', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="sponsor_price" name="sponsor_price" 
                           value="<?php echo esc_attr($settings['sponsor_price']); ?>" class="regular-text">
                    <p class="description"><?php _e('Price in EUR', 'akadimies'); ?></p>
                </td>
            </tr>

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

    <!-- Database Management Section -->
    <div class="database-management" style="margin-top: 50px; padding: 20px; background: #fff; border: 1px solid #ccc;">
        <h2><?php _e('Database Management', 'akadimies'); ?></h2>
        
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
</style>
