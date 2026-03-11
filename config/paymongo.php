<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMongo API Keys
    |--------------------------------------------------------------------------
    |
    | Your PayMongo API keys for processing payments.
    | Get these from your PayMongo Dashboard: https://dashboard.paymongo.com/developers
    |
    */

    'secret_key' => env('PAYMONGO_SECRET_KEY'),
    'public_key' => env('PAYMONGO_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | PayMongo API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for PayMongo API endpoints.
    |
    */

    'api_base_url' => env('PAYMONGO_API_BASE_URL', 'https://api.paymongo.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Payment Link Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for PayMongo Payment Links
    |
    */

    'payment_link' => [
        'currency' => 'PHP',
        'description_prefix' => 'SexEd Platform - ',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Webhook secret for verifying PayMongo webhook signatures
    |
    */

    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),

];
