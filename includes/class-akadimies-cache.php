
```php
// File: /includes/class-akadimies-cache.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesCache {
    private $prefix = 'akadimies_';
    private $default_expiration = 3600; // 1 hour

    public function get($key) {
        return get_transient($this->prefix . $key);
    }

    public function set($key, $value, $expiration = null) {
        $expiration = $expiration ?: $this->default_expiration;
        return set_transient($this->prefix . $key, $value, $expiration);
    }

    public function delete($key) {
        return delete_transient($this->prefix . $key);
    }

    public function flush_group($group) {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_' . $this->prefix . $group . '%'
            )
        );
    }

    public function remember($key, $callback, $expiration = null) {
        $value = $this->get($key);
        
        if ($value === false) {
            $value = $callback();
            $this->set($key, $value, $expiration);
        }
        
        return $value;
    }
}
