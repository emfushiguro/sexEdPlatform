<?php

namespace App\Notifications;

use App\Models\ParentChildAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RelationshipVerificationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ParentChildAccount $relationship,
        private readonly string $action,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $childName = $this->relationship->child?->name ?? 'Dependent';
        $message = match ($this->action) {
            'claim_created' => 'A relationship claim has been created for '.$childName.'.',
            'submitted' => 'Relationship evidence for '.$childName.' has been submitted for review.',
            'resubmitted' => 'New relationship evidence for '.$childName.' has been resubmitted for review.',
            'resubmission_required' => 'Additional relationship evidence is required for '.$childName.'.',
            'approved' => 'The relationship with '.$childName.' has been approved.',
            'rejected' => 'The relationship claim for '.$childName.' has been rejected.',
            'claim_closed' => 'The relationship claim for '.$childName.' has been closed.',
            'revoked' => 'Verification of the relationship with '.$childName.' has been revoked.',
            'deactivated' => 'The relationship with '.$childName.' has been deactivated.',
            'reactivation_requested' => 'Reactivation of the relationship with '.$childName.' has been requested for review.',
            default => 'The relationship verification for '.$childName.' has been updated.',
        };

        return [
            'type' => 'guardian_relationship_verification_'.$this->action,
            'title' => 'Relationship verification '.str_replace('_', ' ', $this->action),
            'message' => $message,
            'parent_child_account_id' => $this->relationship->id,
            'child_user_id' => $this->relationship->child_user_id,
            'relationship' => $this->relationship->relationshipLabel(),
            'status' => $this->relationship->relationship_verified_status,
            'relationship_status' => $this->relationship->relationship_status,
            'action_url' => (int) $notifiable->id === (int) $this->relationship->child_user_id
                ? route('learner.parent.index')
                : route('parent.relationship-verifications.show', $this->relationship),
            'severity' => $this->action === 'approved' ? 'success' : 'warning',
        ];
    }
}
