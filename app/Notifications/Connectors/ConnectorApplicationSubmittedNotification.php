<?php

namespace App\Notifications\Connectors;

use App\Models\Connector;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class ConnectorApplicationSubmittedNotification extends Notification
{
    use Queueable;

    private const EVENTS = ['submitted', 'resubmitted'];

    public function __construct(
        private readonly Connector $connector,
        private readonly string $audience = 'learner',
        private readonly string $event = 'submitted',
    ) {
        if (! in_array($this->audience, ['learner', 'admin'], true)) {
            throw new InvalidArgumentException('Unsupported connector application notification audience.');
        }

        if (! in_array($this->event, self::EVENTS, true)) {
            throw new InvalidArgumentException('Unsupported connector application notification event.');
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isAdmin = $this->audience === 'admin';
        $resubmitted = $this->event === 'resubmitted';

        return [
            'type' => $resubmitted
                ? 'connector_application_resubmitted'
                : 'connector_application_submitted',
            'event' => $this->event,
            'title' => $isAdmin
                ? ($resubmitted ? 'Connector application resubmitted' : 'New connector application')
                : ($resubmitted ? 'Connector evidence resubmitted' : 'Connector application submitted'),
            'message' => $isAdmin
                ? ($resubmitted
                    ? $this->connector->name.' resubmitted connector verification evidence for review.'
                    : $this->connector->name.' submitted a connector application for review.')
                : ($resubmitted
                    ? 'Your updated connector evidence for '.$this->connector->name.' was submitted for review.'
                    : 'Your connector application for '.$this->connector->name.' was submitted for review.'),
            'connector_id' => $this->connector->id,
            'connector_name' => $this->connector->name,
            'status' => 'pending',
            'verification_status' => $this->connector->verification_status,
            'submission_round' => $this->connector->current_submission_round,
            'action_url' => $isAdmin
                ? route('admin.connectors.show', $this->connector)
                : route('connector.status', $this->connector),
            'severity' => 'info',
        ];
    }
}
