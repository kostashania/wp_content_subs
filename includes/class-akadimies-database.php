<?php
if (!defined('ABSPATH')) exit;

class AkadimiesDatabase {
    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // First, drop existing tables to ensure clean installation
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}akadimies_subscription_extensions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}akadimies_payments");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}akadimies_subscriptions");

        // Create subscriptions table
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akadimies_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            subscription_type varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime NULL,
            payment_id varchar(100) NULL,
            amount decimal(10,2) NOT NULL,
            admin_notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate");

        // Create subscription extensions table
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akadimies_subscription_extensions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            duration int(11) NOT NULL,
            previous_end_date datetime NULL,
            new_end_date datetime NOT NULL,
            payment_id varchar(100) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY subscription_id (subscription_id)
        ) $charset_collate");

        // Create payments table
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akadimies_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) NOT NULL,
            extension_id bigint(20) NULL,
            payment_method varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL,
            transaction_id varchar(100) NULL,
            notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY subscription_id (subscription_id),
            KEY extension_id (extension_id)
        ) $charset_collate");

        update_option('akadimies_db_version', '1.3');
    }

    public function get_subscriptions($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'limit' => 10,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
            WHERE status = %s 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $args['status'],
            $args['limit'],
            $args['offset']
        ));
    }

    public function get_subscription($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
            WHERE id = %d",
            $id
        ));
    }

    public function create_subscription($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'akadimies_subscriptions',
            $data,
            array(
                '%d', // user_id
                '%s', // subscription_type
                '%s', // status
                '%s', // start_date
                '%s', // end_date
                '%s', // payment_id
                '%f'  // amount
            )
        );
    }

    public function update_subscription($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'akadimies_subscriptions',
            $data,
            array('id' => $id)
        );
    }

    public function get_subscription_extensions($subscription_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscription_extensions 
            WHERE subscription_id = %d 
            ORDER BY created_at DESC",
            $subscription_id
        ));
    }

    public function create_extension($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'akadimies_subscription_extensions',
            $data,
            array(
                '%d', // subscription_id
                '%f', // amount
                '%d', // duration
                '%s', // previous_end_date
                '%s', // new_end_date
                '%s', // payment_id
                '%s'  // created_at
            )
        );
    }

    public function get_subscription_total_days($subscription_id) {
        global $wpdb;
        
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            return 0;
        }

        // Get base duration
        $base_duration = $this->calculate_days_between_dates($subscription->start_date, $subscription->end_date);

        // Get extensions
        $extensions = $this->get_subscription_extensions($subscription_id);
        $extension_days = 0;
        foreach ($extensions as $extension) {
            $extension_days += $extension->duration;
        }

        return $base_duration + $extension_days;
    }

    private function calculate_days_between_dates($start_date, $end_date) {
        if (!$start_date || !$end_date) {
            return 0;
        }
        
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        return $interval->days;
    }
}
