<?php

return [
    // MVP scope: one commodity, one corridor. The data model stays commodity-generic.
    'commodity' => 'irish-potato',
    'unit' => ['en' => '50 kg bag', 'sw' => 'gunia la kg 50', 'kg' => 50],
    'counties' => ['Nyandarua', 'Nakuru'],
    'places' => [
        'Nyandarua' => ['Ol Kalou', 'Kinangop', 'Ol Joro Orok', 'Engineer', 'Njabini'],
        'Nakuru' => ['Nakuru town', 'Molo', 'Njoro', 'Rongai', 'Bahati'],
    ],
    'markets' => ['Nakuru', 'Ol Kalou', 'Molo', 'Njoro'],
    'reference_market' => 'Nakuru',
    'grades' => ['A', 'B', 'C'],
    'bags' => ['min' => 1, 'max' => 5000],
    // Prices are stored as integer minor units (cents of KES).
    'price' => ['min_minor' => 50000, 'max_minor' => 2000000],
    'stages' => ['harvest', 'handling', 'storage', 'transport', 'market'],
    'loss_categories' => ['damaged', 'spoiled', 'rejected'],
    'loss_reasons' => ['rain', 'bruising', 'rot', 'pests', 'greening', 'delay', 'market_rejected', 'poor_packaging', 'other'],
    'buyer_types' => ['trader', 'hotel', 'processor', 'market_agent', 'other'],
    'vehicle_types' => ['pickup', 'canter', 'lorry', 'other'],
    // Licence statuses a price source can carry. Only these may be imported as published data.
    'licence_statuses' => ['open_licence', 'permission_granted', 'synthetic', 'unconfirmed', 'restricted'],
    'importable_licences' => ['open_licence', 'permission_granted', 'synthetic'],
    'price_stale_days' => 7,
    'price_change_notify_pct' => 10,
    'deletion_grace_days' => (int) env('FR_DELETION_GRACE_DAYS', 7),
    'show_synthetic_banner' => (bool) env('FR_SHOW_SYNTHETIC_BANNER', true),
    'sms_enabled' => (bool) env('FR_SMS_ENABLED', false),
    'mfa_roles' => ['platform_admin', 'coop_admin'],
    'upload' => ['max_kb' => 4096, 'max_px' => 1600, 'mimes' => ['image/jpeg', 'image/png', 'image/webp']],
    'match_notify_threshold' => 60,
];
