<?php

namespace App\Notifications\Learner;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification
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

        return [
            'type' => 'certificate_issued',
            'status' => 'completed',
            'title' => 'Certificate Issued',
            'message' => 'Your certificate for "' . $module->title . '" is now available.',
            'certificate_id' => $this->certificate->id,
            'module_id' => $module->id,
            'module_title' => $module->title,
            'action_url' => route('learner.certificates.show', $this->certificate),
            'severity' => 'success',
        ];
    }
}
