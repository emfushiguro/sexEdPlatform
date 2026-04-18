<?php

namespace App\Notifications\Parent;

use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChildEnrollmentApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ModuleEnrollment $enrollment,
        private readonly User $child,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $module = $this->enrollment->module;

        return [
            'type' => 'child_enrollment_approved',
            'title' => 'Enrollment Approval Recorded',
            'message' => 'You approved ' . $this->child->name . ' for "' . $module->title . '".',
            'child_user_id' => $this->child->id,
            'child_name' => $this->child->name,
            'module_id' => $module->id,
            'module_title' => $module->title,
            'status' => 'approved',
            'action_url' => route('parent.children.enrollments.show', [$this->child, $this->enrollment, 'from' => 'notification']),
            'severity' => 'success',
        ];
    }
}
