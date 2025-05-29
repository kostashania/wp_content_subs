// File: /includes/class-akadimies-statistics.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesStatistics {
    private $cache;

    public function __construct() {
        $this->cache = new AkadimiesCache();
    }

    public function track_profile_view($profile_id) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'akadimies_profile_views',
            array(
                'profile_id' => $profile_id,
                'viewer_ip' => $this->get_visitor_ip(),
                'view_date' => current_time('mysql'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            )
        );

        $this->update_view_count($profile_id);
    }

    public function get_profile_statistics($profile_id, $period = '30') {
        $cache_key = "profile_stats_{$profile_id}_{$period}";
        
        return $this->cache->remember($cache_key, function() use ($profile_id, $period) {
            global $wpdb;
            
            $stats = array(
                'views' => $this->get_view_count($profile_id, $period),
                'interactions' => $this->get_interaction_count($profile_id, $period),
                'popularity_score' => $this->calculate_popularity_score($profile_id),
                'trending_score' => $this->calculate_trending_score($profile_id)
            );
            
            return $stats;
        }, 3600); // Cache for 1 hour
    }

    private function calculate_popularity_score($profile_id) {
        // Complex algorithm considering various factors
        $views = $this->get_view_count($profile_id, 30);
        $interactions = $this->get_interaction_count($profile_id, 30);
        $profile_completeness = $this->get_profile_completeness($profile_id);
        
        return ($views * 0.4) + ($interactions * 0.4) + ($profile_completeness * 0.2);
    }
}
