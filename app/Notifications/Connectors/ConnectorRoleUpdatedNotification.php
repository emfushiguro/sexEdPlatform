<?php

namespace App\Notifications\Connectors;

use App\Models\ConnectorMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorRoleUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ConnectorMembership $membership)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $connector = $this->membership->connector;
        $roleName = $this->membership->role?->name ?? 'member';

        return [
            'type' => 'connector_role_updated',
            'title' => 'Connector role updated',
            'message' => 'Your role in '.$connector->name.' is now '.$roleName.'.',
            'connector_id' => $connector->id,
            'connector_name' => $connector->name,
            'membership_id' => $this->membership->id,
            'role_name' => $roleName,
            'action_url' => route('connector.dashboard', $connector),
            'severity' => 'info',
        ];
    }
}
