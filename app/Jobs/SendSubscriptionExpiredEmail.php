<?php

namespace App\Jobs;

use App\Mail\SubscriptionExpiredMail;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionExpiredEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public Subscription $subscription) {}

    public function handle(): void
    {
        $user = $this->subscription->user;

        if (!$user || !$user->email) {
            return;
        }

        Mail::to($user->email)->send(new SubscriptionExpiredMail($this->subscription));

        Log::info('Subscription expired email sent', [
            'subscription_id' => $this->subscription->id,
            'user_id'         => $user->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('SendSubscriptionExpiredEmail permanently failed — user never notified of expiry', [
            'subscription_id' => $this->subscription->id,
            'user_id'         => $this->subscription->user_id ?? null,
            'error'           => $exception->getMessage(),
            'failed_at'       => now()->toDateTimeString(),
            'action'          => 'Resend manually or via: php artisan queue:retry all',
        ]);
    }
}
