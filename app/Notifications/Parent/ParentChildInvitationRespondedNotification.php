<?php

namespace App\Notifications\Parent;

use App\Enums\ParentChildInvitationStatus;
use App\Models\ParentChildInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ParentChildInvitationRespondedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ParentChildInvitation $invitation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $childName = $this->invitation->child?->name ?? 'Learner';
        $status = $this->invitation->status instanceof ParentChildInvitationStatus
            ? $this->invitation->status
            : ParentChildInvitationStatus::from((string) $this->invitation->status);

        $title = $status === ParentChildInvitationStatus::Accepted
            ? 'Parent Invitation Accepted'
            : 'Parent Invitation Rejected';

        $message = $status === ParentChildInvitationStatus::Accepted
            ? $childName . ' accepted your parent-link invitation.'
            : $childName . ' rejected your parent-link invitation.';

        return [
            'type' => 'parent_child_invitation_responded',
            'title' => $title,
            'message' => $message,
            'invitation_id' => $this->invitation->id,
            'child_user_id' => $this->invitation->child_user_id,
            'child_name' => $childName,
            'status' => $status->value,
            'action_url' => route('parent.children.index'),
            'severity' => $status === ParentChildInvitationStatus::Accepted ? 'success' : 'warning',
        ];
    }
}
