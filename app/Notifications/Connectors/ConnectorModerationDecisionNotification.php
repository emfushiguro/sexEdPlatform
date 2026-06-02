<?php

namespace App\Notifications\Connectors;

use App\Models\Connector;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorModerationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Connector $connector,
        private readonly string $decision,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->decision === 'verified';

        return [
            'type' => $approved ? 'connector_approved' : 'connector_rejected',
            'title' => $approved ? 'Connector approved' : 'Connector rejected',
            'message' => $approved
                ? $this->connector->name.' has been approved and is ready to manage.'
                : $this->connector->name.' was rejected. Review the status page for details.',
            'connector_id' => $this->connector->id,
            'connector_name' => $this->connector->name,
            'status' => $this->decision,
            'action_url' => route('connector.status', $this->connector),
            'severity' => $approved ? 'success' : 'error',
        ];
    }
}
