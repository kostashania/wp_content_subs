// File: uninstall.php
<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Remove plugin tables
$tables = [
    'akadimies_subscriptions',
    'akadimies_transactions',
    'akadimies_profile_views',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

// Remove plugin options
$options = [
    'akadimies_version',
    'akadimies_db_version',
    'akadimies_paypal_settings',
    'player_price',
    'coach_price',
    'sponsor_price',
];

foreach ($options as $option) {
    delete_option($option);
}

// Remove scheduled cron jobs
wp_clear_scheduled_hook('akadimies_daily_subscription_check');
wp_clear_scheduled_hook('akadimies_send_renewal_reminders');
