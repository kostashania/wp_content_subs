// File: /includes/class-akadimies-cron.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesCron {
    public function __construct() {
        add_action('init', array($this, 'schedule_events'));
        add_action('akadimies_daily_subscription_check', array($this, 'check_subscriptions'));
        add_action('akadimies_send_renewal_reminders', array($this, 'send_renewal_reminders'));
    }

    public function schedule_events() {
        if (!wp_next_scheduled('akadimies_daily_subscription_check')) {
            wp_schedule_event(time(), 'daily', 'akadimies_daily_subscription_check');
        }

        if (!wp_next_scheduled('akadimies_send_renewal_reminders')) {
            wp_schedule_event(time(), 'daily', 'akadimies_send_renewal_reminders');
        }
    }

    public function check_subscriptions() {
        global $wpdb;
        
        // Get expired subscriptions
        $expired = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
                WHERE status = 'active' AND end_date < %s",
                current_time('mysql')
            )
        );

        foreach ($expired as $subscription) {
            $this->handle_expired_subscription($subscription);
        }
    }

    private function handle_expired_subscription($subscription) {
        global $wpdb;
        
        // Update subscription status
        $wpdb->update(
            $wpdb->prefix . 'akadimies_subscriptions',
            array('status' => 'expired'),
            array('id' => $subscription->id)
        );

        // Send expiration notification
        $user = get_userdata($subscription->user_id);
        $notifications = new AkadimiesNotifications();
        $notifications->send_expiration_notice($user->user_email, array(
            'user_name' => $user->display_name,
            'subscription_type' => $subscription->subscription_type
        ));

        // Log the expiration
        AkadimiesLogger::log("Subscription expired: {$subscription->id} for user {$subscription->user_id}");
    }

    public function send_renewal_reminders() {
        global $wpdb;
        
        // Get subscriptions expiring in 7 days
        $expiring_soon = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
                WHERE status = 'active' 
                AND end_date BETWEEN %s AND %s",
                current_time('mysql'),
                date('Y-m-d H:i:s', strtotime('+7 days'))
            )
        );

        foreach ($expiring_soon as $subscription) {
            $this->send_renewal_reminder($subscription);
        }
    }
}
