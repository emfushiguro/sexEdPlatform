<?php

namespace App\Notifications\Connectors;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SeminarRegistrationReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Seminar $seminar,
        private readonly User $registrant,
        private readonly string $status,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->seminar->loadMissing('connector');
        $pending = $this->status === 'pending';
        $eventLabel = strtolower((string) $this->seminar->type) === 'webinar' ? 'webinar' : 'seminar';

        return [
            'type' => 'seminar_registration_received',
            'title' => $pending
                ? ucfirst($eventLabel).' registration awaiting approval'
                : 'New '.$eventLabel.' registration',
            'message' => $pending
                ? $this->registrant->name.' requested registration for '.$this->seminar->title.' and is awaiting your approval.'
                : $this->registrant->name.' registered for '.$this->seminar->title.'.',
            'connector_id' => $this->seminar->connector_id,
            'connector_name' => $this->seminar->connector?->name,
            'seminar_id' => $this->seminar->id,
            'seminar_title' => $this->seminar->title,
            'registrant_id' => $this->registrant->id,
            'registrant_name' => $this->registrant->name,
            'registration_status' => $this->status,
            'action_url' => route('connector.seminars.registrants.index', [$this->seminar->connector, $this->seminar]),
            'severity' => $pending ? 'warning' : 'info',
        ];
    }
}
