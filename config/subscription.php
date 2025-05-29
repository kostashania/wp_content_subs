<?php
if (!defined('ABSPATH')) exit;

return [
    'types' => [
        'player' => [
            'name' => 'Player',
            'duration' => 30, // days
            'features' => [
                'Personal profile page',
                'Performance statistics tracking',
                'Team connection features',
                'Match history',
                'Skill assessment tools'
            ],
            'max_photos' => 10,
            'max_videos' => 3,
            'can_message' => true
        ],
        'coach' => [
            'name' => 'Coach',
            'duration' => 30,
            'features' => [
                'Professional coach profile',
                'Team management tools',
                'Training program creator',
                'Performance analytics',
                'Direct messaging with players',
                'Certificate verification'
            ],
            'max_photos' => 20,
            'max_videos' => 5,
            'can_message' => true,
            'can_create_teams' => true
        ],
        'sponsor' => [
            'name' => 'Sponsor',
            'duration' => 30,
            'features' => [
                'Brand profile page',
                'Advertisement placement',
                'Sponsorship opportunities',
                'Event promotion',
                'Analytics dashboard',
                'Direct contact with teams'
            ],
            'max_photos' => 30,
            'max_videos' => 10,
            'can_message' => true,
            'can_promote_events' => true
        ]
    ],
    'trial_period' => 7, // days
    'grace_period' => 3, // days after expiration
    'renewal_reminder_days' => [7, 3, 1],
    'auto_renewal' => true
];
