<?php

namespace App\Notifications\Connectors;

use App\Models\ConnectorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorInvitationRespondedNotification extends Notification
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
        $inviteeName = $this->invitation->invitedUser?->name ?? $this->invitation->email;
        $accepted = $this->invitation->status === 'accepted';

        return [
            'type' => $accepted ? 'connector_invitation_accepted' : 'connector_invitation_rejected',
            'title' => $accepted ? 'Connector invitation accepted' : 'Connector invitation rejected',
            'message' => $inviteeName.' '.$this->invitation->status.' the invitation to '.$connector->name.'.',
            'connector_id' => $connector->id,
            'connector_name' => $connector->name,
            'invitation_id' => $this->invitation->id,
            'status' => $this->invitation->status,
            'action_url' => route('connector.members.index', $connector),
            'severity' => $accepted ? 'success' : 'info',
        ];
    }
}
