// File: /includes/class-akadimies-logger.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesLogger {
    private static $log_file;
    
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/akadimies-debug.log';
    }
    
    public static function log($message, $type = 'info') {
        if (!self::$log_file) {
            self::init();
        }
        
        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] [%s] %s\n", 
            $timestamp, 
            strtoupper($type), 
            is_array($message) ? json_encode($message) : $message
        );
        
        error_log($log_entry, 3, self::$log_file);
    }

    public static function log_payment($payment_data) {
        self::log([
            'action' => 'payment',
            'data' => $payment_data
        ], 'payment');
    }

    public static function log_error($error) {
        self::log($error, 'error');
    }
}
