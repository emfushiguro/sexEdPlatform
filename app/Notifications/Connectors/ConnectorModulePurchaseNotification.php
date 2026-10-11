<?php

namespace App\Notifications\Connectors;

use App\Models\ConnectorModulePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorModulePurchaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ConnectorModulePurchase $purchase,
        public readonly string $status,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $attention = $this->status === ConnectorModulePurchase::STATUS_ATTENTION_REQUIRED;

        return [
            'type' => 'connector_module_purchase',
            'status' => $this->status,
            'title' => $attention ? 'Connector module purchase needs attention' : 'Connector module purchase completed',
            'message' => $attention
                ? 'A Connector module payment was received but needs administrator review.'
                : 'Your Connector module purchase is complete.',
            'connector_module_purchase_id' => $this->purchase->id,
            'connector_id' => $this->purchase->connector_id,
            'module_id' => $this->purchase->module_id,
            'seat_capacity' => (int) $this->purchase->seat_capacity,
        ];
    }
}
