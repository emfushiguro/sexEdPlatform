<?php

namespace App\Notifications\Admin;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewPaymentTransactionNotification extends Notification
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
            'type' => 'new_payment_transaction',
            'status' => (string) $this->payment->status->value,
            'title' => 'Payment Transaction Update',
            'message' => 'Payment from ' . $userName . ' is now ' . strtolower((string) $this->payment->status->value) . '.',
            'payment_id' => $this->payment->id,
            'subscription_id' => $this->payment->subscription_id,
            'transaction_id' => $this->payment->transaction_id,
            'amount' => (float) $this->payment->amount,
            'action_url' => route('admin.payments.show', $this->payment),
            'severity' => $this->payment->isFailed() ? 'error' : 'success',
        ];
    }
}
