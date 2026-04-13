<?php

namespace App\Notifications\Parent;

use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChildEnrollmentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ModuleEnrollment $enrollment,
        private readonly User $child,
        private readonly ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $module = $this->enrollment->module;
        $reason = $this->reason ? trim($this->reason) : null;

        $message = 'You rejected ' . $this->child->name . '\'s enrollment request for "' . $module->title . '".';

        if ($reason) {
            $message .= ' Reason: ' . $reason;
        }

        return [
            'type' => 'child_enrollment_rejected',
            'title' => 'Enrollment Rejection Recorded',
            'message' => $message,
            'child_user_id' => $this->child->id,
            'child_name' => $this->child->name,
            'module_id' => $module->id,
            'module_title' => $module->title,
            'reason' => $reason,
            'status' => 'rejected',
            'action_url' => route('parent.children.show', $this->child),
            'severity' => 'error',
        ];
    }
}
