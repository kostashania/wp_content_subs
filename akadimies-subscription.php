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

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AKADIMIES_VERSION', '1.0.0');
define('AKADIMIES_PATH', plugin_dir_path(__FILE__));
define('AKADIMIES_URL', plugin_dir_url(__FILE__));

class AkadimiesPlugin {
    private static $instance = null;
    private $config = array();
    private $admin = null;
    private $database = null;
    private $subscription = null;
    private $payments = null;
    private $profiles = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        try {
            // Load core functionality
            $this->load_configs();
            $this->load_dependencies();
            $this->init_components();
            $this->add_hooks();
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="error"><p>Akadimies Plugin Error: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    private function load_configs() {
        $this->config = array();
        $config_files = array(
            'email' => array(),
            'media' => array(),
            'subscription' => array(),
            'paypal' => array(),
            'notifications' => array(),
            'cache' => array(),
            'analytics' => array(),
            'security' => array()
        );

        foreach ($config_files as $config => $default) {
            $file = AKADIMIES_PATH . "config/{$config}.php";
            if (file_exists($file)) {
                $this->config[$config] = include $file;
            } else {
                $this->config[$config] = array('enabled' => false);
            }
        }
    }

    private function load_dependencies() {
        // Required core files
        $required_files = array('admin', 'database', 'subscription');
        
        foreach ($required_files as $file) {
            $path = AKADIMIES_PATH . "includes/class-akadimies-{$file}.php";
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function init_components() {
        // Initialize core components
        if (class_exists('AkadimiesAdmin')) {
            $this->admin = new AkadimiesAdmin();
        }
        if (class_exists('AkadimiesDatabase')) {
            $this->database = new AkadimiesDatabase();
        }
        if (class_exists('AkadimiesSubscription')) {
            $this->subscription = new AkadimiesSubscription();
        }
    }

    private function add_hooks() {
        // Core hooks
        add_action('init', array($this, 'init'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register shortcodes
        add_shortcode('subscription_form', array($this, 'render_subscription_form'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Load translations
        load_plugin_textdomain('akadimies', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize admin if in admin area
        if (is_admin() && $this->admin) {
            $this->admin->init();
        }
    }

    public function enqueue_scripts() {
        // Styles
        if (file_exists(AKADIMIES_PATH . 'assets/css/subscription-form.css')) {
            wp_enqueue_style(
                'akadimies-subscription',
                AKADIMIES_URL . 'assets/css/subscription-form.css',
                array(),
                AKADIMIES_VERSION
            );
        }

        // Scripts
        if (file_exists(AKADIMIES_PATH . 'assets/js/subscription.js')) {
            wp_enqueue_script(
                'akadimies-subscription',
                AKADIMIES_URL . 'assets/js/subscription.js',
                array('jquery'),
                AKADIMIES_VERSION,
                true
            );

            wp_localize_script('akadimies-subscription', 'akadimiesAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('akadimies-subscription')
            ));
        }
    }

    public function activate() {
        if ($this->database) {
            $this->database->install();
        }
    }

    public function render_subscription_form() {
        ob_start();
        ?>
        <div class="subscription-plans">
            <div class="subscription-plan player-plan">
                <div class="plan-header">
                    <h2><?php _e('Player Subscription', 'akadimies'); ?></h2>
                    <div class="price">
                        <?php echo esc_html(get_option('akadimies_player_price', '29.99')); ?> € / <?php _e('month', 'akadimies'); ?>
                    </div>
                </div>

                <div class="plan-features">
                    <ul>
                        <li><?php _e('Personal profile page', 'akadimies'); ?></li>
                        <li><?php _e('Performance statistics tracking', 'akadimies'); ?></li>
                        <li><?php _e('Team connection features', 'akadimies'); ?></li>
                        <li><?php _e('Match history', 'akadimies'); ?></li>
                        <li><?php _e('Skill assessment tools', 'akadimies'); ?></li>
                    </ul>
                </div>

                <div class="plan-footer">
                    <button class="subscribe-button" data-plan="player" data-price="<?php echo esc_attr(get_option('akadimies_player_price', '29.99')); ?>">
                        <?php _e('Subscribe Now', 'akadimies'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_config($key = null) {
        if ($key === null) {
            return $this->config;
        }
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
}

// Initialize plugin
function akadimies_init() {
    return AkadimiesPlugin::getInstance();
}

// Start the plugin
add_action('plugins_loaded', 'akadimies_init', 10);
