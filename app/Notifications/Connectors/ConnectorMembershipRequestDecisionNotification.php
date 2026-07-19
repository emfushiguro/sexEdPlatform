<?php

namespace App\Notifications\Connectors;

use App\Models\ConnectorMembershipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorMembershipRequestDecisionNotification extends Notification
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
        $approved = $this->membershipRequest->status === 'approved';

        return [
            'type' => $approved ? 'connector_membership_request_approved' : 'connector_membership_request_rejected',
            'title' => $approved ? 'Membership approved' : 'Membership rejected',
            'message' => $approved
                ? 'Your request to join '.$connector->name.' was approved.'
                : 'Your request to join '.$connector->name.' was rejected.',
            'connector_id' => $connector->id,
            'connector_name' => $connector->name,
            'membership_request_id' => $this->membershipRequest->id,
            'status' => $this->membershipRequest->status,
            'action_url' => route('connectors.show', $connector),
            'severity' => $approved ? 'success' : 'info',
        ];
    }
}
