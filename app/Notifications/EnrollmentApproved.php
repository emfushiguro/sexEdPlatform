<?php

namespace App\Notifications;

use App\Models\ModuleEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentApproved extends Notification
{
    use Queueable;

    public function __construct(public ModuleEnrollment $enrollment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'enrollment_approved',
            'title'      => 'Enrollment Approved',
            'message'    => 'Your enrollment in "' . $this->enrollment->module->title . '" has been approved. You can now start learning!',
            'module_id'  => $this->enrollment->module_id,
            'module_url' => route('learner.modules.show', $this->enrollment->module),
        ];
    }
}
