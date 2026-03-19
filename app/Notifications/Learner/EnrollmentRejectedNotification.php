<?php

namespace App\Notifications\Learner;

use App\Models\ModuleEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ModuleEnrollment $enrollment, private readonly string $instructorName)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $module = $this->enrollment->module;
        $reasonCode = $this->enrollment->rejection_reason_code ?: 'unspecified_reason';
        $reasonNote = $this->enrollment->rejection_reason_note;

        $message = 'Your enrollment request for "' . $module->title . '" was rejected by ' . $this->instructorName . '. '
            . 'Reason: ' . $reasonCode . '.';

        if (! empty($reasonNote)) {
            $message .= ' Note: ' . $reasonNote;
        }

        return [
            'type' => 'enrollment_rejected',
            'title' => 'Enrollment Not Approved',
            'message' => $message,
            'module_id' => $module->id,
            'module_title' => $module->title,
            'rejection_reason_code' => $reasonCode,
            'rejection_reason_note' => $reasonNote,
            'instructor_name' => $this->instructorName,
        ];
    }
}
