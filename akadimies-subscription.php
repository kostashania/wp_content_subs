<?php
/*
Plugin Name: Akadimies Subscription Manager
Plugin URI: https://akadimies.eu
Description: Subscription management system for players, coaches, and sponsors
Version: 1.0
Author: Your Name
Text Domain: akadimies
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Define plugin constants
define('AKADIMIES_VERSION', '1.0.0');
define('AKADIMIES_PATH', plugin_dir_path(__FILE__));
define('AKADIMIES_URL', plugin_dir_url(__FILE__));

class AkadimiesPlugin {
    private static $instance = null;
    private $config = [];
    private $admin = null;
    private $database = null;
    private $loaded_dependencies = [];

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register autoloader
        spl_autoload_register([$this, 'autoload']);
        
        // Initialize in stages
        add_action('plugins_loaded', [$this, 'load_configs'], 5);
        add_action('plugins_loaded', [$this, 'load_dependencies'], 10);
        add_action('init', [$this, 'init'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Register AJAX handlers
        add_action('wp_ajax_process_subscription', [$this, 'handle_subscription']);
        add_action('wp_ajax_nopriv_process_subscription', [$this, 'handle_non_logged_subscription']);
    }

    public function autoload($class) {
        // Convert class name to file path
        $prefix = 'Akadimies\\';
        $base_dir = AKADIMIES_PATH . 'includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }

    public function load_configs() {
        $config_files = [
            'email', 'media', 'subscription', 'paypal',
            'notifications', 'cache', 'analytics', 'security'
        ];

        foreach ($config_files as $config) {
            $file = AKADIMIES_PATH . "config/{$config}.php";
            if (file_exists($file)) {
                $this->config[$config] = include $file;
            } else {
                $this->config[$config] = ['enabled' => false];
            }
        }
    }

    public function load_dependencies() {
        // Load required core files first
        $core_files = [
            'database' => 'class-akadimies-database.php',
            'admin' => 'class-akadimies-admin.php'
        ];

        foreach ($core_files as $key => $file) {
            $path = AKADIMIES_PATH . 'includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
                $this->loaded_dependencies[] = $key;
            }
        }

        // Initialize core components
        if (in_array('database', $this->loaded_dependencies)) {
            $this->database = new AkadimiesDatabase();
        }

        if (in_array('admin', $this->loaded_dependencies) && is_admin()) {
            $this->admin = new AkadimiesAdmin();
        }
    }

    public function init() {
        // Load text domain
        load_plugin_textdomain(
            'akadimies',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );

        // Register shortcodes
        add_shortcode('subscription_form', [$this, 'render_subscription_form']);

        // Initialize admin if available
        if ($this->admin !== null) {
            $this->admin->init();
        }
    }

    public function enqueue_scripts() {
        // Enqueue frontend styles
        if (file_exists(AKADIMIES_PATH . 'assets/css/subscription-form.css')) {
            wp_enqueue_style(
                'akadimies-subscription',
                AKADIMIES_URL . 'assets/css/subscription-form.css',
                [],
                AKADIMIES_VERSION
            );
        }

        // Enqueue frontend scripts
        if (file_exists(AKADIMIES_PATH . 'assets/js/subscription.js')) {
            wp_enqueue_script(
                'akadimies-subscription',
                AKADIMIES_URL . 'assets/js/subscription.js',
                ['jquery'],
                AKADIMIES_VERSION,
                true
            );

            wp_localize_script('akadimies-subscription', 'akadimiesAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('akadimies-subscription')
            ]);
        }
    }

    public function render_subscription_form() {
        ob_start();
        $template_path = AKADIMIES_PATH . 'templates/frontend/subscription-types/player.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="subscription-form">';
            echo '<div class="subscription-plan player-plan">';
            echo '<h2>' . esc_html__('Player Subscription', 'akadimies') . '</h2>';
            echo '<div class="price">' . esc_html(get_option('akadimies_player_price', '29.99')) . ' € / ' . esc_html__('month', 'akadimies') . '</div>';
            echo '<button class="subscribe-button">' . esc_html__('Subscribe Now', 'akadimies') . '</button>';
            echo '</div>';
            echo '</div>';
        }
        
        $content = ob_get_clean();
        return $content;
    }

    public function handle_subscription() {
        check_ajax_referer('akadimies-subscription', 'nonce');

        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Please log in to subscribe', 'akadimies')
            ]);
        }

        // Get and validate subscription data
        $plan = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

        if (!in_array($plan, ['player', 'coach', 'sponsor'])) {
            wp_send_json_error([
                'message' => __('Invalid subscription plan', 'akadimies')
            ]);
        }

        try {
            // Prepare subscription data
            $subscription_data = [
                'user_id' => $user_id,
                'subscription_type' => $plan,
                'status' => 'pending',
                'amount' => $price,
                'start_date' => current_time('mysql'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ];

            // Insert subscription
            global $wpdb;
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'akadimies_subscriptions',
                $subscription_data,
                [
                    '%d', // user_id
                    '%s', // subscription_type
                    '%s', // status
                    '%f', // amount
                    '%s', // start_date
                    '%s'  // end_date
                ]
            );

            if ($inserted) {
                $subscription_id = $wpdb->insert_id;

                // Log the subscription creation
                error_log("New subscription created: ID {$subscription_id} for user {$user_id}");

                // Send success response
                wp_send_json_success([
                    'message' => __('Subscription created successfully', 'akadimies'),
                    'subscription_id' => $subscription_id,
                    'redirect' => home_url('/subscription-confirmation/')
                ]);
            } else {
                throw new Exception(__('Failed to create subscription', 'akadimies'));
            }

        } catch (Exception $e) {
            error_log("Subscription creation failed: " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handle_non_logged_subscription() {
        wp_send_json_error([
            'message' => __('Please log in to subscribe', 'akadimies'),
            'redirect' => wp_login_url(wp_get_referer())
        ]);
    }

    public function get_config($key = null) {
        if ($key === null) {
            return $this->config;
        }
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
}

// Initialize plugin safely
try {
    function akadimies_init() {
        return AkadimiesPlugin::getInstance();
    }
    add_action('plugins_loaded', 'akadimies_init', 5);
} catch (Exception $e) {
    add_action('admin_notices', function() use ($e) {
        $message = sprintf(
            'Akadimies Plugin Error: %s',
            esc_html($e->getMessage())
        );
        echo '<div class="error"><p>' . $message . '</p></div>';
    });
}
