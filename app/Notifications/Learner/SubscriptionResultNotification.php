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
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isSuccess = in_array($this->status, ['completed', 'success', 'active'], true);
        if ($this->payment && \App\Services\GuardianSubscriptionGiftService::isGift($this->payment)) {
            return [
                'type' => 'subscription_result', 'status' => $this->status,
                'title' => $isSuccess ? 'Gift Subscription Payment Successful' : 'Gift Subscription Payment Update',
                'message' => 'Gift subscription for '.data_get($this->payment->payment_details, 'gift.beneficiary_name').'. '
                    .($isSuccess ? 'Payment is confirmed and dependent access is available.' : 'Payment has not been confirmed.'),
                'payment_id' => $this->payment->id, 'subscription_id' => $this->subscription?->id,
                'action_url' => $isSuccess ? route('payment.receipt', $this->payment) : route('payment.history'),
                'severity' => $isSuccess ? 'success' : 'info',
            ];
        }

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
