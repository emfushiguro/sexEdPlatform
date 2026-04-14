<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
    ],

    'google_cloud' => [
        'api_key' => env('GOOGLE_API_KEY'),
        'translation_endpoint' => env('GOOGLE_TRANSLATION_ENDPOINT', 'https://translation.googleapis.com/language/translate/v2'),
        'tts_endpoint' => env('GOOGLE_TTS_ENDPOINT', 'https://texttospeech.googleapis.com/v1/text:synthesize'),
    ],

    'mailtrap' => [
        'api_token' => env('MAILTRAP_API_TOKEN'),
        'api_base_url' => env('MAILTRAP_API_BASE_URL', 'https://send.api.mailtrap.io/api/send'),
        'inbox_id' => env('MAILTRAP_INBOX_ID'),
        'from' => [
            'address' => env('MAILTRAP_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@demomailtrap.co')),
            'name' => env('MAILTRAP_FROM_NAME', env('MAIL_FROM_NAME', config('app.name'))),
        ],
        'test_to' => env('MAILTRAP_TEST_TO'),
    ],

];
