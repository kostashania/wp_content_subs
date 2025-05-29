
<?php
if (!defined('ABSPATH')) exit;

return array(
    'enabled' => true,
return [
    'tracking' => [
        'profile_views' => true,
        'interactions' => true,
        'search_queries' => true,
        'user_behavior' => true
    ],
    'metrics' => [
        'popularity_factors' => [
            'views_weight' => 0.4,
            'interactions_weight' => 0.4,
            'profile_completeness_weight' => 0.2
        ],
        'trending_period' => 7, // days
        'cache_duration' => 3600 // 1 hour
    ],
    'reports' => [
        'automated' => [
            'enabled' => true,
            'frequency' => 'weekly',
            'recipients' => ['admin@akadimies.eu']
        ],
        'export_formats' => ['csv', 'pdf', 'excel']
    ]
];
