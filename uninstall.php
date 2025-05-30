<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Access WordPress database object
global $wpdb;

// Drop custom tables
$tables = array(
    'akadimies_subscriptions',
    'akadimies_payments'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

// Delete plugin options
$options = array(
    'akadimies_db_version',
    'akadimies_player_price',
    'akadimies_coach_price',
    'akadimies_sponsor_price',
    'akadimies_paypal_client_id',
    'akadimies_paypal_secret',
    'akadimies_paypal_sandbox'
);

foreach ($options as $option) {
    delete_option($option);
}

// Clean up any transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%akadimies_%'");

// Delete any user meta related to the plugin
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '%akadimies_%'");

// Delete any post meta related to the plugin
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '%akadimies_%'");

// Clear any scheduled cron events
wp_clear_scheduled_hook('akadimies_daily_subscription_check');
wp_clear_scheduled_hook('akadimies_cleanup_expired_subscriptions');

// Log the uninstallation (optional, for debugging)
if (WP_DEBUG === true) {
    error_log('Akadimies Subscription plugin uninstalled');
}
