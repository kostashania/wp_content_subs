
<?php
if (!defined('ABSPATH')) exit;

return array(
    'enabled' => true,
return [
    'enabled' => true,
    'driver' => 'file', // file, redis, memcached
    'prefix' => 'akadimies_',
    'default_ttl' => 3600,
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0
    ],
    'memcached' => [
        'servers' => [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100]
        ]
    ],
    'rules' => [
        'profile_data' => 3600,
        'member_directory' => 1800,
        'subscription_plans' => 86400,
        'analytics' => 900
    ]
];
