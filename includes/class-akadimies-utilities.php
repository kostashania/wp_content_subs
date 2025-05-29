// File: /includes/class-akadimies-utilities.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesUtilities {
    public static function format_price($price, $currency = 'EUR') {
        return number_format($price, 2) . ' ' . $currency;
    }

    public static function get_subscription_status_label($status) {
        $labels = array(
            'active' => __('Active', 'akadimies'),
            'expired' => __('Expired', 'akadimies'),
            'pending' => __('Pending', 'akadimies'),
            'cancelled' => __('Cancelled', 'akadimies')
        );

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    public static function get_date_difference($date1, $date2) {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        return $interval->days;
    }

    public static function sanitize_profile_data($data) {
        $clean = array();
        
        if (isset($data['display_name'])) {
            $clean['display_name'] = sanitize_text_field($data['display_name']);
        }
        
        if (isset($data['position'])) {
            $clean['position'] = sanitize_text_field($data['position']);
        }
        
        if (isset($data['age'])) {
            $clean['age'] = intval($data['age']);
        }
        
        if (isset($data['social'])) {
            $clean['social'] = array_map('esc_url_raw', $data['social']);
        }
        
        return $clean;
    }
}
