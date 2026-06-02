<?php

namespace App\Notifications\Connectors;

use App\Models\Connector;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorApplicationSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Connector $connector,
        private readonly string $audience = 'learner',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isAdmin = $this->audience === 'admin';

        return [
            'type' => 'connector_application_submitted',
            'title' => $isAdmin ? 'New connector application' : 'Connector application submitted',
            'message' => $isAdmin
                ? $this->connector->name.' submitted a connector application for review.'
                : 'Your connector application for '.$this->connector->name.' was submitted for review.',
            'connector_id' => $this->connector->id,
            'connector_name' => $this->connector->name,
            'status' => 'pending',
            'action_url' => $isAdmin
                ? route('admin.connectors.show', $this->connector)
                : route('connector.status', $this->connector),
            'severity' => 'info',
        ];
    }
}
