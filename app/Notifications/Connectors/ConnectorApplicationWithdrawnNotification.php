<?php

namespace App\Notifications\Connectors;

use App\Models\Connector;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorApplicationWithdrawnNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Connector $connector)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'connector_application_withdrawn',
            'title' => 'Connector application withdrawn',
            'message' => $this->connector->name.' withdrew a pending connector application.',
            'connector_id' => $this->connector->id,
            'connector_name' => $this->connector->name,
            'status' => 'withdrawn',
            'action_url' => route('admin.connectors.show', $this->connector),
            'severity' => 'info',
        ];
    }
}
