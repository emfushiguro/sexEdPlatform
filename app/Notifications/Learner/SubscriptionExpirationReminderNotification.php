<?php

namespace App\Notifications\Learner;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionExpirationReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Subscription $subscription,
        private readonly int $daysRemaining,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $plural = $this->daysRemaining === 1 ? 'day' : 'days';

        return [
            'type' => 'subscription_expiration_reminder',
            'status' => 'expiring_soon',
            'title' => 'Subscription Expiring Soon',
            'message' => 'Your subscription will expire in ' . $this->daysRemaining . ' ' . $plural . '. Renew now to keep access.',
            'subscription_id' => $this->subscription->id,
            'days_remaining' => $this->daysRemaining,
            'action_url' => route('subscription.index'),
            'severity' => 'info',
        ];
    }
}
