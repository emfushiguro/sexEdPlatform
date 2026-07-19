<?php

namespace App\Notifications\Seminars;

use App\Enums\SeminarStatus;
use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SeminarModerationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Seminar $seminar,
        private readonly string $status,
        private readonly ?string $reason = null,
        private readonly ?string $note = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->status === SeminarStatus::Approved->value;

        return [
            'type' => 'seminar_moderation_decision',
            'title' => $approved ? 'Seminar Approved' : 'Seminar Rejected',
            'message' => $approved
                ? '"'.$this->seminar->title.'" was approved and can now be published.'
                : '"'.$this->seminar->title.'" was rejected. Reason: '.$this->reasonLabel().'.',
            'seminar_id' => $this->seminar->id,
            'seminar_title' => $this->seminar->title,
            'status' => $this->status,
            'reason' => $this->reason,
            'reason_label' => $this->reasonLabel(),
            'note' => $this->note,
            'action_url' => route('connector.seminars.show', [$this->seminar->connector_id, $this->seminar]),
            'severity' => $approved ? 'success' : 'error',
        ];
    }

    private function reasonLabel(): ?string
    {
        return $this->reason ? (config('seminars.rejection_reasons')[$this->reason] ?? $this->reason) : null;
    }
}
