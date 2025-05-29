<?php
if (!defined('ABSPATH')) exit;

class AkadimiesSubscription {
    private static $instance = null;
    private $profiles;
    private $payments;
    private $notifications;
    private $admin;

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->profiles = new AkadimiesProfiles();
        $this->payments = new AkadimiesPayments();
        $this->notifications = new AkadimiesNotifications();
        $this->admin = new AkadimiesAdmin();
    }

    public function init() {
        add_action('init', array($this, 'register_post_types'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('subscription_form', array($this, 'subscription_form_shortcode'));
        
        // Initialize components
        $this->profiles->init();
        $this->payments->init();
        $this->notifications->init();
        $this->admin->init();
    }

    public function register_post_types() {
        // Registration code here
    }

    public function enqueue_scripts() {
        wp_enqueue_style('akadimies-style', AKADIMIES_URL . 'assets/css/subscription-form.css');
        wp_enqueue_script('akadimies-script', AKADIMIES_URL . 'assets/js/subscription.js', array('jquery'), AKADIMIES_VERSION, true);
    }
}
