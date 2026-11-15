<?php

namespace App\Notifications;

use App\Models\ParentChildAccount;
use Illuminate\Notifications\Notification;

class AdultAuthorityReviewStatusNotification extends Notification
{
    public function __construct(private readonly ParentChildAccount $relationship, private readonly string $status) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'adult_authority_review_status',
            'parent_child_account_id' => $this->relationship->id,
            'title' => 'Representative authority review completed',
            'message' => $this->status === 'approved' ? 'Your authority evidence was approved. Open this notice to continue to the dependent activation code.' : 'Your authority evidence was not approved. Open this notice to submit another document.',
            'status' => $this->status,
            'action_url' => route('parent.relationship-verifications.show', $this->relationship, false),
            'severity' => $this->status === 'approved' ? 'success' : 'warning',
        ];
    }
}
