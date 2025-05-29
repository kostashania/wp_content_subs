<?php
if (!defined('ABSPATH')) exit;

// Check if settings were saved
if (isset($_POST['submit'])) {
    if (check_admin_referer('akadimies_settings', 'akadimies_settings_nonce')) {
        // Save settings
        update_option('akadimies_player_price', floatval($_POST['player_price']));
        update_option('akadimies_coach_price', floatval($_POST['coach_price']));
        update_option('akadimies_sponsor_price', floatval($_POST['sponsor_price']));
        update_option('akadimies_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
        update_option('akadimies_paypal_secret', sanitize_text_field($_POST['paypal_secret']));
        update_option('akadimies_paypal_sandbox', isset($_POST['paypal_sandbox']) ? 1 : 0);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'akadimies') . '</p></div>';
    }
}

// Get current values
$player_price = get_option('akadimies_player_price', '29.99');
$coach_price = get_option('akadimies_coach_price', '49.99');
$sponsor_price = get_option('akadimies_sponsor_price', '99.99');
$paypal_client_id = get_option('akadimies_paypal_client_id', '');
$paypal_secret = get_option('akadimies_paypal_secret', '');
$paypal_sandbox = get_option('akadimies_paypal_sandbox', 1);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('akadimies_settings', 'akadimies_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="player_price"><?php _e('Player Subscription Price', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="player_price" name="player_price" 
                           value="<?php echo esc_attr($player_price); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="coach_price"><?php _e('Coach Subscription Price', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="coach_price" name="coach_price" 
                           value="<?php echo esc_attr($coach_price); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="sponsor_price"><?php _e('Sponsor Subscription Price', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="sponsor_price" name="sponsor_price" 
                           value="<?php echo esc_attr($sponsor_price); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="paypal_client_id"><?php _e('PayPal Client ID', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="text" id="paypal_client_id" name="paypal_client_id" 
                           value="<?php echo esc_attr($paypal_client_id); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="paypal_secret"><?php _e('PayPal Secret', 'akadimies'); ?></label>
                </th>
                <td>
                    <input type="password" id="paypal_secret" name="paypal_secret" 
                           value="<?php echo esc_attr($paypal_secret); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php _e('PayPal Sandbox Mode', 'akadimies'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="paypal_sandbox" value="1" 
                               <?php checked($paypal_sandbox, 1); ?>>
                        <?php _e('Enable Sandbox Mode', 'akadimies'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
