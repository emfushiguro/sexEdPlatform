<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Received – Your Subscription is Active',
            from: config('mail.from.address', 'noreply@sexedplatform.com')
        );
    }

    public function content(): Content
    {
        $subscription = $this->payment->subscription;

        return new Content(
            markdown: 'emails.payment-receipt',
            with: [
                'payment'      => $this->payment,
                'subscription' => $subscription,
                'user'         => $this->payment->user,
                'amount'       => number_format($this->payment->amount, 2),
                'paidAt'       => $this->payment->paid_at?->format('M d, Y h:i A'),
                'planName'     => $subscription?->getPlanLabel() ?? 'Premium Subscription',
                'endDate'      => $subscription?->end_date?->format('M d, Y'),
                'invoiceUrl'   => route('subscription.index'),
            ]
        );
    }
}
