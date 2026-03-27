<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Subscription is Expiring Soon',
            from: config('mail.from.address', 'noreply@sexedplatform.com')
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-expiring',
            with: [
                'subscription' => $this->subscription,
                'user' => $this->subscription->user,
                'daysUntilExpiry' => $this->subscription->daysUntilExpiry(),
                'renewUrl' => route('subscription.upgrade'),
                'planName' => $this->subscription->getPlanLabel(),
                'expiryDate' => $this->subscription->end_date->format('M d, Y'),
            ]
        );
    }
}