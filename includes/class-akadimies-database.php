<?php
if (!defined('ABSPATH')) exit;

class AkadimiesDatabase {
    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Create subscriptions table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akadimies_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            subscription_type varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime NULL,
            payment_id varchar(100) NULL,
            amount decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Create payments table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akadimies_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) NOT NULL,
            payment_method varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL,
            receipt_number varchar(50) NULL,
            transaction_id varchar(100) NULL,
            notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY subscription_id (subscription_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }

        add_option('akadimies_db_version', '1.0');
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

    public function get_payments($subscription_id = null) {
        global $wpdb;
        
        $sql = "SELECT * FROM {$wpdb->prefix}akadimies_payments";
        if ($subscription_id) {
            $sql .= $wpdb->prepare(" WHERE subscription_id = %d", $subscription_id);
        }
        $sql .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($sql);
    }

    public function create_payment($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'akadimies_payments',
            $data,
            array(
                '%d', // subscription_id
                '%s', // payment_method
                '%f', // amount
                '%s', // status
                '%s', // receipt_number
                '%s'  // notes
            )
        );
    }
}
