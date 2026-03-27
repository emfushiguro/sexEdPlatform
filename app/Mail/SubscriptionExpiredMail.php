<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Subscription Has Expired',
            from: config('mail.from.address', 'noreply@sexedplatform.com')
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-expired',
            with: [
                'subscription' => $this->subscription,
                'user'         => $this->subscription->user,
                'planName'     => $this->subscription->getPlanLabel(),
                'expiredAt'    => $this->subscription->end_date?->format('M d, Y'),
                'renewUrl'     => route('subscription.upgrade'),
            ]
        );
    }
}
