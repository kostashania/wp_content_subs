<?php
if (!defined('ABSPATH')) exit;

return [
    'sandbox_mode' => true,
    'client_id' => '',
    'client_secret' => '',
    'webhook_id' => '',
    'settings' => [
        'currency' => 'EUR',
        'locale' => 'en_US',
        'intent' => 'CAPTURE',
        'return_url' => home_url('payment-success'),
        'cancel_url' => home_url('payment-cancelled')
    ],
    'webhook_events' => [
        'PAYMENT.CAPTURE.COMPLETED',
        'PAYMENT.CAPTURE.DENIED',
        'BILLING.SUBSCRIPTION.ACTIVATED',
        'BILLING.SUBSCRIPTION.CANCELLED'
    ]
];
