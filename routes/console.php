<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('send-mailtrap-test {to? : Recipient email address}', function (?string $to = null) {
    $apiToken = (string) config('services.mailtrap.api_token');

    if ($apiToken === '') {
        $this->error('MAILTRAP_API_TOKEN is missing in .env');

        return 1;
    }

    $fromAddress = (string) config('services.mailtrap.from.address');
    $fromName = (string) config('services.mailtrap.from.name');
    $toAddress = $to ?: (string) config('services.mailtrap.test_to');

    if ($toAddress === '') {
        $this->error('Recipient is required. Pass one or set MAILTRAP_TEST_TO in .env');

        return 1;
    }

    $apiBaseUrl = rtrim((string) config('services.mailtrap.api_base_url', 'https://send.api.mailtrap.io/api/send'), '/');
    $inboxId = (string) config('services.mailtrap.inbox_id');
    $endpoint = $inboxId !== ''
        ? "https://sandbox.api.mailtrap.io/api/send/{$inboxId}"
        : $apiBaseUrl;

    $payload = [
        'from' => [
            'email' => $fromAddress,
            'name' => $fromName,
        ],
        'to' => [
            [
                'email' => $toAddress,
            ],
        ],
        'subject' => 'You are awesome!',
        'text' => 'Congrats for sending test email with Mailtrap!',
        'category' => 'Integration Test',
    ];

    try {
        $response = Http::withToken($apiToken)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            $this->error("Mailtrap API error ({$response->status()}): ".$response->body());

            return 1;
        }

        $this->info('Mailtrap response:');
        $this->line((string) json_encode($response->json(), JSON_PRETTY_PRINT));

        return 0;
    } catch (\Throwable $e) {
        $this->error('Failed to send Mailtrap test email: '.$e->getMessage());

        return 1;
    }
})->purpose('Send a test email using Mailtrap API');

// Schedule automatic module publishing
Schedule::command('modules:publish-scheduled')->everyMinute();

// Schedule subscription expiration check - runs every minute for short-duration test plans
Schedule::command('subscriptions:expire')->everyMinute();

// Schedule subscription renewals and dunning - runs hourly
Schedule::command('subscriptions:process-renewals')->hourly();

// Schedule analytics report - runs weekly on Mondays
Schedule::command('analytics:generate-report weekly')->weeklyOn(1, '8:00');
