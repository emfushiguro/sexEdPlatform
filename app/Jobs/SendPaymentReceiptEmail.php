<?php

namespace App\Jobs;

use App\Mail\PaymentReceiptMail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentReceiptEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public Payment $payment) {}

    public function handle(): void
    {
        $user = $this->payment->user;

        if (!$user || !$user->email) {
            return;
        }

        Mail::to($user->email)->send(new PaymentReceiptMail($this->payment));

        Log::info('Payment receipt email sent', [
            'payment_id' => $this->payment->id,
            'user_id'    => $user->id,
            'email'      => $user->email,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // All retries exhausted. Payment receipt email was never delivered to user.
        Log::critical('SendPaymentReceiptEmail permanently failed — user never received receipt', [
            'payment_id' => $this->payment->id,
            'user_id'    => $this->payment->user_id ?? null,
            'error'      => $exception->getMessage(),
            'failed_at'  => now()->toDateTimeString(),
            'action'     => 'Resend manually from admin panel or via: php artisan queue:retry all',
        ]);
    }
}
