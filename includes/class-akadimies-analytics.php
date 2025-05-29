// File: /includes/class-akadimies-analytics.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesAnalytics {
    public function get_subscription_stats($period = '30') {
        global $wpdb;
        
        $stats = array(
            'total_active' => $this->get_active_subscriptions_count(),
            'new_subscriptions' => $this->get_new_subscriptions_count($period),
            'revenue' => $this->get_revenue_stats($period),
            'by_type' => $this->get_subscriptions_by_type(),
            'conversion_rate' => $this->get_conversion_rate($period)
        );

        return $stats;
    }

    private function get_active_subscriptions_count() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}akadimies_subscriptions 
            WHERE status = 'active'"
        );
    }

    private function get_new_subscriptions_count($days) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}akadimies_subscriptions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }

    private function get_revenue_stats($days) {
        global $wpdb;
        
        $results = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    SUM(amount) as total_revenue,
                    AVG(amount) as average_revenue,
                    COUNT(DISTINCT user_id) as unique_customers
                FROM {$wpdb->prefix}akadimies_transactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                AND status = 'completed'",
                $days
            )
        );

        return array(
            'total' => $results->total_revenue ?: 0,
            'average' => $results->average_revenue ?: 0,
            'customers' => $results->unique_customers ?: 0
        );
    }

    public function generate_report($start_date, $end_date) {
        $report_data = array(
            'period' => array(
                'start' => $start_date,
                'end' => $end_date
            ),
            'subscriptions' => $this->get_subscription_data($start_date, $end_date),
            'revenue' => $this->get_revenue_data($start_date, $end_date),
            'user_growth' => $this->get_user_growth_data($start_date, $end_date)
        );

        return $this->format_report($report_data);
    }

    private function format_report($data) {
        ob_start();
        ?>
        <div class="analytics-report">
            <h2>Subscription Analytics Report</h2>
            <p>Period: <?php echo date('F j, Y', strtotime($data['period']['start'])); ?> - 
                      <?php echo date('F j, Y', strtotime($data['period']['end'])); ?></p>
            
            <div class="report-section">
                <h3>Subscription Overview</h3>
                <div class="stats-grid">
                    <div class="stat-box">
                        <span class="stat-label">Total Active</span>
                        <span class="stat-value"><?php echo $data['subscriptions']['active']; ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">New Subscriptions</span>
                        <span class="stat-value"><?php echo $data['subscriptions']['new']; ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Revenue</span>
                        <span class="stat-value"><?php echo AkadimiesUtilities::format_price($data['revenue']['total']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
