<?php

namespace App\Listeners;

use App\Events\PaymentSuccessful;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendPaymentReceiptEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandlePaymentSuccessful implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(PaymentSuccessful $event): void
    {
        $payment = $event->payment->fresh();
        if (!$payment) {
            return;
        }

        $details = is_array($payment->payment_details) ? $payment->payment_details : [];

        if ((bool) data_get($details, 'post_payment_jobs_dispatched', false)) {
            return;
        }

        $details['post_payment_jobs_dispatched'] = true;
        $details['post_payment_jobs_dispatched_at'] = now()->toDateTimeString();

        $payment->forceFill([
            'payment_details' => $details,
        ])->saveQuietly();

        // Kick off slow operations as background jobs
        GenerateInvoiceJob::dispatch($payment)->onQueue('invoices');
        SendPaymentReceiptEmail::dispatch($payment)->onQueue('emails');
    }
}
