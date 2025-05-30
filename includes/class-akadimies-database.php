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
            admin_notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            UNIQUE KEY unique_active_subscription (user_id, subscription_type, status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }

        add_option('akadimies_db_version', '1.0');
    }

    public function uninstall() {
        global $wpdb;
        
        // Drop tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}akadimies_subscriptions");
        
        // Delete options
        delete_option('akadimies_db_version');
        delete_option('akadimies_player_price');
        delete_option('akadimies_coach_price');
        delete_option('akadimies_sponsor_price');
        delete_option('akadimies_paypal_client_id');
        delete_option('akadimies_paypal_secret');
        delete_option('akadimies_paypal_sandbox');
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
        
        // Check for existing active subscription
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
            WHERE user_id = %d 
            AND subscription_type = %s 
            AND status = 'active'",
            $data['user_id'],
            $data['subscription_type']
        ));

        if ($existing) {
            // Extend the existing subscription
            $new_end_date = $existing->end_date ? 
                date('Y-m-d H:i:s', strtotime($existing->end_date . ' +30 days')) :
                date('Y-m-d H:i:s', strtotime('+30 days'));

            return $wpdb->update(
                $wpdb->prefix . 'akadimies_subscriptions',
                array(
                    'end_date' => $new_end_date,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
        }

        // Create new subscription
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

    public function delete_subscription($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'akadimies_subscriptions',
            array('id' => $id),
            array('%d')
        );
    }

    public function consolidate_subscriptions() {
        global $wpdb;

        // Get all users with multiple active subscriptions of the same type
        $duplicates = $wpdb->get_results(
            "SELECT user_id, subscription_type, COUNT(*) as count, 
             GROUP_CONCAT(id ORDER BY created_at DESC) as subscription_ids,
             MAX(end_date) as latest_end_date
             FROM {$wpdb->prefix}akadimies_subscriptions
             WHERE status = 'active'
             GROUP BY user_id, subscription_type
             HAVING count > 1"
        );

        foreach ($duplicates as $duplicate) {
            // Get all subscription IDs except the most recent one
            $subscription_ids = explode(',', $duplicate->subscription_ids);
            $keep_id = array_shift($subscription_ids); // Keep the most recent

            // Update the kept subscription with the latest end date
            $wpdb->update(
                $wpdb->prefix . 'akadimies_subscriptions',
                array(
                    'end_date' => $duplicate->latest_end_date,
                    'updated_at' => current_time('mysql'),
                    'admin_notes' => 'Consolidated from multiple subscriptions'
                ),
                array('id' => $keep_id)
            );

            // Mark other subscriptions as consolidated
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}akadimies_subscriptions 
                SET status = 'consolidated', 
                    admin_notes = 'Consolidated into subscription ID: %d',
                    updated_at = %s
                WHERE id IN (" . implode(',', $subscription_ids) . ")",
                $keep_id,
                current_time('mysql')
            ));
        }

        return count($duplicates);
    }

    public function cleanup_expired_subscriptions() {
        global $wpdb;

        return $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}akadimies_subscriptions 
            SET status = 'expired',
                updated_at = %s
            WHERE status = 'active' 
            AND end_date < %s",
            current_time('mysql'),
            current_time('mysql')
        ));
    }
}
