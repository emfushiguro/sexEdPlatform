<?php

namespace App\Notifications\Learner;

use App\Models\ModuleEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ModuleEnrollment $enrollment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $module = $this->enrollment->module;

        return [
            'type' => 'enrollment_approved',
            'title' => 'Enrollment Approved',
            'message' => 'Your enrollment in "' . $module->title . '" has been approved. You can now start learning.',
            'module_id' => $module->id,
            'module_title' => $module->title,
            'action_url' => route('learner.modules.show', $module),
        ];
    }
}
