// File: /includes/class-akadimies-search.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesSearch {
    public function search_members($query, $type = null, $page = 1, $per_page = 10) {
        global $wpdb;

        $args = array(
            'post_type' => 'member_profile',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $query,
            'meta_query' => array()
        );

        if ($type) {
            $args['meta_query'][] = array(
                'key' => '_profile_type',
                'value' => $type
            );
        }

        $results = new WP_Query($args);
        
        return array(
            'items' => $this->format_search_results($results->posts),
            'total' => $results->found_posts,
            'pages' => ceil($results->found_posts / $per_page)
        );
    }

    private function format_search_results($posts) {
        $formatted = array();
        
        foreach ($posts as $post) {
            $profile_data = get_post_meta($post->ID, '_profile_data', true);
            
            $formatted[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $profile_data['type'],
                'image' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
                'excerpt' => wp_trim_words($post->post_content, 20),
                'url' => get_permalink($post->ID)
            );
        }
        
        return $formatted;
    }
}
