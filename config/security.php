
<?php
if (!defined('ABSPATH')) exit;

return array(
    'enabled' => true,
return [
    'rate_limiting' => [
        'enabled' => true,
        'max_attempts' => [
            'login' => 5,
            'profile_update' => 10,
            'message_send' => 20
        ],
        'timeframe' => 3600, // 1 hour
        'lockout_duration' => 1800 // 30 minutes
    ],
    'allowed_html' => [
        'basic' => [
            'tags' => ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a'],
            'attributes' => [
                'a' => ['href', 'title', 'target']
            ]
        ],
        'profile' => [
            'tags' => ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'h2', 'h3', 'img'],
            'attributes' => [
                'a' => ['href', 'title', 'target'],
                'img' => ['src', 'alt', 'title', 'width', 'height']
            ]
        ]
    ],
    'captcha' => [
        'enabled' => true,
        'type' => 'recaptcha',
        'site_key' => '',
        'secret_key' => ''
    ]
];
