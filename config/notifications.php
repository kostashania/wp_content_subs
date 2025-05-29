<?php
if (!defined('ABSPATH')) exit;

return [
    'channels' => [
        'email' => true,
        'database' => true,
        'push' => false
    ],
    'push_config' => [
        'provider' => 'firebase',
        'api_key' => '',
        'project_id' => ''
    ],
    'events' => [
        'subscription_created' => ['email', 'database'],
        'subscription_expired' => ['email', 'database'],
        'payment_received' => ['email', 'database'],
        'profile_viewed' => ['database'],
        'message_received' => ['email', 'database', 'push']
    ],
    'queue' => [
        'enabled' => true,
        'driver' => 'database',
        'retry_after' => 90,
        'max_attempts' => 3
    ]
];
