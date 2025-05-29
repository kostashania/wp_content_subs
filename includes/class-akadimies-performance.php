// File: /includes/class-akadimies-performance.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesPerformance {
    private $cache;
    
    public function __construct() {
        $this->cache = new AkadimiesCache();
    }

    public function optimize_queries() {
        add_filter('posts_join', array($this, 'optimize_profile_joins'));
        add_filter('posts_where', array($this, 'optimize_profile_where'));
        add_action('pre_get_posts', array($this, 'optimize_archive_queries'));
    }

    public function optimize_profile_joins($join) {
        global $wpdb;
        
        if (!is_admin() && is_post_type_archive('member_profile')) {
            $join .= " LEFT JOIN {$wpdb->postmeta} pm_type ON pm_type.post_id = {$wpdb->posts}.ID 
                      AND pm_type.meta_key = '_profile_type'";
        }
        
        return $join;
    }

    public function cache_profile_data($profile_id) {
        $profile_data = array(
            'basic' => $this->get_basic_profile_data($profile_id),
            'stats' => $this->get_profile_stats($profile_id),
            'activity' => $this->get_recent_activity($profile_id)
        );

        $this->cache->set("profile_{$profile_id}", $profile_data, 3600);
        return $profile_data;
    }

    public function monitor_performance() {
        global $wpdb;
        $start_time = microtime(true);
        $queries_start = $wpdb->num_queries;

        add_action('shutdown', function() use ($start_time, $queries_start) {
            global $wpdb;
            
            $execution_time = microtime(true) - $start_time;
            $total_queries = $wpdb->num_queries - $queries_start;
            
            AkadimiesLogger::log([
                'execution_time' => $execution_time,
                'total_queries' => $total_queries,
                'peak_memory' => memory_get_peak_usage(true)
            ], 'performance');
        });
    }
}
