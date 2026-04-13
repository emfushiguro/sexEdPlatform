<?php

namespace App\Notifications\Instructor;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LearnerCertificateIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Certificate $certificate)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $module = $this->certificate->module;
        $learner = $this->certificate->user;

        return [
            'type' => 'learner_certificate_issued',
            'status' => 'completed',
            'title' => 'Learner Earned a Certificate',
            'message' => $learner->name . ' earned a certificate for "' . $module->title . '".',
            'certificate_id' => $this->certificate->id,
            'module_id' => $module->id,
            'module_title' => $module->title,
            'learner_id' => $learner->id,
            'learner_name' => $learner->name,
            'action_url' => route('instructor.modules.show', $module),
            'severity' => 'success',
        ];
    }
}
