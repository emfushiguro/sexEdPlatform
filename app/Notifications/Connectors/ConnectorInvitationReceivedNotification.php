<?php

namespace App\Notifications\Connectors;

use App\Models\ConnectorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorInvitationReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ConnectorInvitation $invitation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $connector = $this->invitation->connector;
        $inviterName = $this->invitation->inviter?->name ?? 'A connector admin';

        return [
            'type' => 'connector_invitation_received',
            'title' => 'Connector invitation',
            'message' => $inviterName.' invited you to join '.$connector->name.'.',
            'connector_id' => $connector->id,
            'connector_name' => $connector->name,
            'invitation_id' => $this->invitation->id,
            'status' => 'pending',
            'action_url' => route('connector.status', $connector),
            'severity' => 'info',
        ];
    }
}
