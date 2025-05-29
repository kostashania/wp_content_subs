// File: /includes/class-akadimies-security.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesSecurity {
    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die('Security check failed', 'Security Error', array('response' => 403));
        }
    }

    public static function sanitize_input($data, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'int':
                return intval($data);
            case 'float':
                return floatval($data);
            case 'html':
                return wp_kses_post($data);
            default:
                return sanitize_text_field($data);
        }
    }

    public static function validate_subscription_type($type) {
        $valid_types = array('player', 'coach', 'sponsor');
        return in_array($type, $valid_types);
    }

    public static function check_rate_limit($user_id, $action, $limit = 5, $period = 3600) {
        $transient_key = "rate_limit_{$action}_{$user_id}";
        $attempts = get_transient($transient_key);
        
        if (false === $attempts) {
            set_transient($transient_key, 1, $period);
            return true;
        }
        
        if ($attempts >= $limit) {
            return false;
        }
        
        set_transient($transient_key, $attempts + 1, $period);
        return true;
    }
}
