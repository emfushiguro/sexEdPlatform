<?php

namespace App\Notifications;

use App\Models\ModuleEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentRejected extends Notification
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
            'type'      => 'enrollment_rejected',
            'title'     => 'Enrollment Not Approved',
            'message'   => 'Your enrollment request for "' . $this->enrollment->module->title . '" was not approved. Contact your instructor for more information.',
            'module_id' => $this->enrollment->module_id,
        ];
    }
}
