<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public int $nextRetryDays
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Failed - Action Required',
            from: config('mail.from.address', 'noreply@sexedplatform.com')
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-failed',
            with: [
                'payment' => $this->payment,
                'subscription' => $this->payment->subscription,
                'user' => $this->payment->subscription->user,
                'nextRetryDays' => $this->nextRetryDays,
                'amount' => number_format($this->payment->amount, 2),
                'updatePaymentUrl' => route('payment.update', $this->payment->id),
            ]
        );
    }
}