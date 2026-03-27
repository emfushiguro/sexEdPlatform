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
        $payment = $event->payment;

        // Kick off slow operations as background jobs
        GenerateInvoiceJob::dispatch($payment)->onQueue('invoices');
        SendPaymentReceiptEmail::dispatch($payment)->onQueue('emails');
    }
}
