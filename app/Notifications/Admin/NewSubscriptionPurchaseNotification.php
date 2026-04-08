<?php

namespace App\Notifications\Admin;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSubscriptionPurchaseNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $userName = $this->payment->user?->name ?? 'Unknown user';

        return [
            'type' => 'new_subscription_purchase',
            'status' => 'completed',
            'title' => 'New Subscription Purchase',
            'message' => $userName . ' completed a subscription purchase.',
            'payment_id' => $this->payment->id,
            'subscription_id' => $this->payment->subscription_id,
            'amount' => (float) $this->payment->amount,
            'action_url' => route('admin.payments.show', $this->payment),
            'severity' => 'success',
        ];
    }
}
