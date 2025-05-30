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
            KEY status (status)
        ) $charset_collate;";

        // Create subscription extensions table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akadimies_subscription_extensions (
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
        ) $charset_collate;";

        // Create payments table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akadimies_payments (
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
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }

        add_option('akadimies_db_version', '1.1');
    }

    public function create_subscription($data) {
        global $wpdb;
        
        // Check for existing active subscription of same type
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
            WHERE user_id = %d 
            AND subscription_type = %s 
            AND status = 'active'",
            $data['user_id'],
            $data['subscription_type']
        ));

        if ($existing) {
            // Calculate new end date
            $new_end_date = null;
            if ($existing->end_date) {
                // Add new duration to existing end date
                $new_end_date = date('Y-m-d H:i:s', strtotime($existing->end_date . ' + ' . $data['duration'] . ' days'));
            } else {
                // Add new duration to current date
                $new_end_date = date('Y-m-d H:i:s', strtotime('+' . $data['duration'] . ' days'));
            }

            // Start transaction
            $wpdb->query('START TRANSACTION');

            try {
                // Update existing subscription
                $updated = $wpdb->update(
                    $wpdb->prefix . 'akadimies_subscriptions',
                    array(
                        'end_date' => $new_end_date,
                        'amount' => $existing->amount + $data['amount'],
                        'updated_at' => current_time('mysql'),
                        'admin_notes' => isset($existing->admin_notes) ? 
                            $existing->admin_notes . "\n" . sprintf(
                                'Subscription extended on %s. Added: %d days, €%s',
                                current_time('mysql'),
                                $data['duration'],
                                $data['amount']
                            ) : 
                            sprintf(
                                'Subscription extended on %s. Added: %d days, €%s',
                                current_time('mysql'),
                                $data['duration'],
                                $data['amount']
                            )
                    ),
                    array('id' => $existing->id),
                    array('%s', '%f', '%s', '%s'),
                    array('%d')
                );

                if ($updated === false) {
                    throw new Exception('Failed to update subscription');
                }

                // Create extension record
                $extension_inserted = $wpdb->insert(
                    $wpdb->prefix . 'akadimies_subscription_extensions',
                    array(
                        'subscription_id' => $existing->id,
                        'amount' => $data['amount'],
                        'duration' => $data['duration'],
                        'previous_end_date' => $existing->end_date,
                        'new_end_date' => $new_end_date,
                        'payment_id' => isset($data['payment_id']) ? $data['payment_id'] : null,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%f', '%d', '%s', '%s', '%s', '%s')
                );

                if ($extension_inserted === false) {
                    throw new Exception('Failed to create extension record');
                }

                $wpdb->query('COMMIT');
                return $existing->id;

            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                error_log('Subscription extension failed: ' . $e->getMessage());
                return false;
            }
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

    public function get_subscriptions($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'limit' => 10,
            'offset' => 0,
            'user_id' => null,
            'subscription_type' => null
        );

        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $values = array();

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }

        if ($args['subscription_type']) {
            $where[] = 'subscription_type = %s';
            $values[] = $args['subscription_type'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akadimies_subscriptions 
            {$where_clause}
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $values
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

    public function get_subscription_total_amount($subscription_id) {
        global $wpdb;
        
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            return 0;
        }

        // Get base amount
        $total = $subscription->amount;

        // Add extension amounts
        $extensions = $this->get_subscription_extensions($subscription_id);
        foreach ($extensions as $extension) {
            $total += $extension->amount;
        }

        return $total;
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
