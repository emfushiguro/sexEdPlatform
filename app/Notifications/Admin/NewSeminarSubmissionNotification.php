<?php

namespace App\Notifications\Admin;

use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSeminarSubmissionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Seminar $seminar)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $seminar = $this->seminar->loadMissing('connector');
        $connectorName = $seminar->connector?->name ?? 'Connector';

        return [
            'type' => 'new_seminar_submission',
            'title' => 'New Seminar Submitted for Review',
            'message' => $connectorName.' submitted "'.$seminar->title.'" for moderation.',
            'seminar_id' => $seminar->id,
            'seminar_title' => $seminar->title,
            'connector_id' => $seminar->connector_id,
            'connector_name' => $connectorName,
            'status' => 'pending_review',
            'action_url' => route('admin.seminars.show', $seminar),
            'severity' => 'info',
        ];
    }
}
