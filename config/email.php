
<?php
if (!defined('ABSPATH')) exit;

return array(
    'enabled' => true,
return [
    'from_name' => get_bloginfo('name'),
    'from_email' => get_bloginfo('admin_email'),
    'templates' => [
        'welcome' => [
            'subject' => 'Welcome to Akadimies',
            'template' => 'welcome.php'
        ],
        'renewal' => [
            'subject' => 'Your subscription is expiring soon',
            'template' => 'renewal-reminder.php'
        ],
        'payment_success' => [
            'subject' => 'Payment Confirmation',
            'template' => 'payment-success.php'
        ],
        'subscription_expired' => [
            'subject' => 'Your subscription has expired',
            'template' => 'subscription-expired.php'
        ]
    ],
    'smtp' => [
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'auth' => true
    ]
];
