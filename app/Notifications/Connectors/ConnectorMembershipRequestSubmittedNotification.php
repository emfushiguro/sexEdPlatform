<?php

namespace App\Notifications\Connectors;

use App\Models\ConnectorMembershipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorMembershipRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ConnectorMembershipRequest $membershipRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $connector = $this->membershipRequest->connector;
        $user = $this->membershipRequest->user;

        return [
            'type' => 'connector_membership_request_submitted',
            'title' => 'Membership request',
            'message' => ($user?->name ?? 'A user').' requested to join '.$connector->name.'.',
            'connector_id' => $connector->id,
            'connector_name' => $connector->name,
            'membership_request_id' => $this->membershipRequest->id,
            'action_url' => route('connector.members.index', $connector),
            'severity' => 'info',
        ];
    }
}
