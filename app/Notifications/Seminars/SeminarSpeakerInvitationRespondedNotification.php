<?php

namespace App\Notifications\Seminars;

use App\Models\SeminarSpeaker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SeminarSpeakerInvitationRespondedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SeminarSpeaker $speaker)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $seminar = $this->speaker->seminar;
        $status = $this->speaker->status;
        $accepted = $status === 'accepted';
        $applied = $status === 'applied';
        $speakerUser = (int) $notifiable->id === (int) $this->speaker->user_id;

        return [
            'type' => $applied ? 'seminar_speaker_application_submitted' : ($accepted ? 'seminar_speaker_invitation_accepted' : 'seminar_speaker_invitation_declined'),
            'title' => $applied ? 'Speaker application submitted' : ($accepted ? ($speakerUser ? 'Speaker application approved' : 'Speaker invitation accepted') : ($speakerUser ? 'Speaker application rejected' : 'Speaker invitation declined')),
            'message' => $this->speaker->display_name.' '.$status.' for '.$seminar->title.'.',
            'seminar_id' => $seminar->id,
            'seminar_title' => $seminar->title,
            'seminar_speaker_id' => $this->speaker->id,
            'connector_id' => $seminar->connector_id,
            'action_url' => $speakerUser ? route('seminars.show', $seminar) : route('connector.seminars.show', [$seminar->connector, $seminar]),
            'severity' => $accepted ? 'success' : 'info',
        ];
    }
}
