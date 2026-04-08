<?php

namespace App\Notifications\Learner;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionResultNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $status,
        private readonly ?Subscription $subscription = null,
        private readonly ?Payment $payment = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isSuccess = in_array($this->status, ['completed', 'success', 'active'], true);

        return [
            'type' => 'subscription_result',
            'status' => $this->status,
            'title' => $isSuccess ? 'Subscription Payment Successful' : 'Subscription Payment Failed',
            'message' => $isSuccess
                ? 'Your subscription payment was successful and your access remains active.'
                : 'Your subscription payment failed. Please update payment details to avoid interruption.',
            'subscription_id' => $this->subscription?->id,
            'payment_id' => $this->payment?->id,
            'transaction_id' => $this->payment?->transaction_id,
            'action_url' => route('subscription.index'),
            'severity' => $isSuccess ? 'success' : 'error',
        ];
    }
}
