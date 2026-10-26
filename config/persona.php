<?php

return [
    'enabled' => (bool) env('PERSONA_ENABLED', false),
    'environment' => env('PERSONA_ENVIRONMENT', 'sandbox'),
    'enforced_from' => env('PERSONA_ENFORCED_FROM'),
    'api_key' => env('PERSONA_API_KEY'),
    'api_base_url' => 'https://api.withpersona.com/api/v1',
    'hosted_flow_url' => 'https://inquiry.withpersona.com/verify',
    'api_version' => '2026-09-29',
    'webhook_secret' => env('PERSONA_WEBHOOK_SECRET'),
    'webhook_tolerance_seconds' => 300,
    'templates' => [
        'selfie' => env('PERSONA_SELFIE_TEMPLATE_ID'),
        'govid_selfie' => env('PERSONA_GOVID_SELFIE_TEMPLATE_ID'),
    ],
    'government_id_classes' => [
        'passport' => 'pp',
        'drivers_license' => 'dl',
        'umid' => 'umid',
        'postal_id' => 'pid',
        'voters_id' => 'vid',
    ],
    'media_hosts' => ['files.withpersona.com'],
    'media_max_bytes' => 10 * 1024 * 1024,
    'connect_timeout_seconds' => 5,
    'timeout_seconds' => 15,
];
