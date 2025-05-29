// File: /includes/class-akadimies-activator.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesActivator {
    public static function activate() {
        // Create necessary database tables
        self::create_tables();
        
        // Create required pages
        self::create_pages();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Set version
        update_option('akadimies_version', AKADIMIES_VERSION);
    }

    private static function create_pages() {
        $pages = [
            'member-directory' => [
                'title' => 'Member Directory',
                'content' => '[akadimies_member_directory]'
            ],
            'subscription' => [
                'title' => 'Subscribe',
                'content' => '[akadimies_subscription_form]'
            ],
            'profile' => [
                'title' => 'My Profile',
                'content' => '[akadimies_profile_edit]'
            ]
        ];

        foreach ($pages as $slug => $page) {
            if (!get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ]);
            }
        }
    }

    private static function set_default_options() {
        $defaults = [
            'player_price' => '29.99',
            'coach_price' => '49.99',
            'sponsor_price' => '99.99',
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }
}
