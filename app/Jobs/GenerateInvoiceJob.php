<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(public Payment $payment) {}

    public function handle(InvoiceService $invoiceService): void
    {
        // Skip if invoice already exists for this payment
        if ($this->payment->invoice()->exists()) {
            return;
        }

        $invoiceService->generateInvoice($this->payment);

        Log::info('Invoice generated via job', [
            'payment_id' => $this->payment->id,
            'user_id'    => $this->payment->user_id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // All retries exhausted. Invoice was NOT generated for a completed payment.
        // Admin must manually generate it: php artisan tinker → app(InvoiceService::class)->generateInvoice($payment)
        Log::critical('GenerateInvoiceJob permanently failed — manual invoice required', [
            'payment_id' => $this->payment->id,
            'user_id'    => $this->payment->user_id ?? null,
            'amount'     => $this->payment->amount ?? null,
            'error'      => $exception->getMessage(),
            'failed_at'  => now()->toDateTimeString(),
            'action'     => 'php artisan tinker then: app(App\\Services\\InvoiceService::class)->generateInvoice(App\\Models\\Payment::find(' . $this->payment->id . '))',
        ]);
    }
}
