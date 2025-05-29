// File: /includes/class-akadimies-api.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesAPI {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('akadimies/v1', '/subscriptions', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_subscriptions'),
                'permission_callback' => array($this, 'check_admin_permission')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_subscription'),
                'permission_callback' => '__return_true'
            )
        ));

        register_rest_route('akadimies/v1', '/profiles/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_profile'),
            'permission_callback' => '__return_true'
        ));
    }

    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    public function get_subscriptions($request) {
        global $wpdb;
        
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 10;
        $status = $request->get_param('status');
        
        $query = "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions";
        if ($status) {
            $query .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM ({$query}) AS t");
        
        $query .= " LIMIT %d OFFSET %d";
        $offset = ($page - 1) * $per_page;
        
        $subscriptions = $wpdb->get_results(
            $wpdb->prepare($query, $per_page, $offset)
        );
        
        return new WP_REST_Response([
            'data' => $subscriptions,
            'total' => (int) $total,
            'pages' => ceil($total / $per_page)
        ], 200);
    }

    public function create_subscription($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'User must be logged in', array('status' => 401));
        }

        $subscription_type = $request->get_param('type');
        $payment_id = $request->get_param('payment_id');
        
        // Verify payment with PayPal
        $payment_verified = AkadimiesPayments::verify_payment($payment_id);
        if (!$payment_verified) {
            return new WP_Error('payment_failed', 'Payment verification failed', array('status' => 400));
        }

        $subscription = new AkadimiesSubscription();
        $result = $subscription->create(array(
            'user_id' => $user_id,
            'type' => $subscription_type,
            'payment_id' => $payment_id
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 201);
    }
}
