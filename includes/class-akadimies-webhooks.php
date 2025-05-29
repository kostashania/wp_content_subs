// File: /includes/class-akadimies-webhooks.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesWebhooks {
    private $paypal_ipn_url = 'https://ipnpb.paypal.com/cgi-bin/webscr';
    private $sandbox_ipn_url = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

    public function __construct() {
        add_action('init', array($this, 'register_webhook_endpoints'));
        add_action('akadimies_handle_paypal_ipn', array($this, 'handle_paypal_ipn'));
    }

    public function register_webhook_endpoints() {
        add_rewrite_rule(
            'akadimies-webhook/paypal/?$',
            'index.php?akadimies_webhook=paypal',
            'top'
        );
        flush_rewrite_rules();
    }

    public function handle_paypal_ipn() {
        // Verify PayPal IPN
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }

        $req = 'cmd=_notify-validate';
        foreach ($myPost as $key => $value) {
            $value = urlencode($value);
            $req .= "&$key=$value";
        }

        $verify_url = get_option('paypal_sandbox_mode') ? $this->sandbox_ipn_url : $this->paypal_ipn_url;
        
        $response = wp_remote_post($verify_url, array(
            'body' => $req,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            AkadimiesLogger::log_error('IPN verification failed: ' . $response->get_error_message());
            return;
        }

        if (strcmp($response['body'], "VERIFIED") == 0) {
            $this->process_verified_ipn($myPost);
        }
    }

    private function process_verified_ipn($data) {
        // Log IPN data
        AkadimiesLogger::log('PayPal IPN received', 'ipn');
        AkadimiesLogger::log($data, 'ipn');

        switch ($data['txn_type']) {
            case 'subscr_payment':
                $this->handle_subscription_payment($data);
                break;
            
            case 'subscr_cancel':
                $this->handle_subscription_cancellation($data);
                break;
            
            case 'subscr_failed':
                $this->handle_failed_payment($data);
                break;
        }
    }

    private function handle_subscription_payment($data) {
        global $wpdb;

        // Update subscription status
        $wpdb->update(
            $wpdb->prefix . 'akadimies_subscriptions',
            array(
                'status' => 'active',
                'last_payment_date' => current_time('mysql'),
                'next_payment_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
            ),
            array('payment_profile_id' => $data['subscr_id'])
        );

        // Record transaction
        $wpdb->insert(
            $wpdb->prefix . 'akadimies_transactions',
            array(
                'subscription_id' => $data['custom'],
                'transaction_id' => $data['txn_id'],
                'amount' => $data['mc_gross'],
                'status' => 'completed',
                'payment_method' => 'paypal',
                'created_at' => current_time('mysql')
            )
        );

        // Send confirmation email
        $subscription = $this->get_subscription_by_profile_id($data['subscr_id']);
        if ($subscription) {
            $notifications = new AkadimiesNotifications();
            $notifications->send_payment_confirmation(
                $subscription->user_id,
                $data['mc_gross'],
                $data['txn_id']
            );
        }
    }
}
