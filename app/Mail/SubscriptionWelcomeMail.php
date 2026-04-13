<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Premium - Your Subscription is Active',
            from: config('mail.from.address', 'noreply@sexedplatform.com')
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-welcome',
            with: [
                'subscription' => $this->subscription,
                'user'         => $this->subscription->user,
                'planName'     => $this->subscription->getPlanLabel(),
                'endDate'      => $this->subscription->end_date?->format('M d, Y'),
                'dashboardUrl' => route('learner.dashboard'),
            ]
        );
    }
}
