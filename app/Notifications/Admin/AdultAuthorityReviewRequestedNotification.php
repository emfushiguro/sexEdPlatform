<?php

namespace App\Notifications\Admin;

use App\Models\ParentChildAccount;
use Illuminate\Notifications\Notification;

class AdultAuthorityReviewRequestedNotification extends Notification
{
    public function __construct(private readonly ParentChildAccount $relationship) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'adult_authority_review_requested',
            'parent_child_account_id' => $this->relationship->id,
            'title' => 'Adult representative authority requires review',
            'message' => 'A Guardian submitted authority evidence for a dependent account.',
            'action_url' => route('admin.parent-verifications.relationships.show', $this->relationship, false),
            'severity' => 'info',
        ];
    }
}
