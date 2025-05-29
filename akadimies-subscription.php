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
        if (file_exists(AKADIMIES_PATH . 'templates/frontend/subscription-types/player.php')) {
            include AKADIMIES_PATH . 'templates/frontend/subscription-types/player.php';
        } else {
            echo '<div class="subscription-form">Subscription form template not found.</div>';
        }
        return ob_get_clean();
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
