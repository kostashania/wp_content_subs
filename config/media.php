<?php
if (!defined('ABSPATH')) exit;

return [
    'image' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
        'max_width' => 2000,
        'max_height' => 2000,
        'thumbnails' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600]
        ]
    ],
    'video' => [
        'max_size' => 104857600, // 100MB
        'allowed_types' => ['video/mp4', 'video/quicktime'],
        'max_duration' => 300, // 5 minutes
        'thumbnail_generation' => true
    ],
    'storage' => [
        'provider' => 'local', // local, s3, etc.
        's3' => [
            'bucket' => '',
            'region' => '',
            'access_key' => '',
            'secret_key' => ''
        ]
    ]
];
