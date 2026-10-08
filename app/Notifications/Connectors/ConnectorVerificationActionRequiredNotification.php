<?php

namespace App\Notifications\Connectors;

use App\Models\Connector;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class ConnectorVerificationActionRequiredNotification extends Notification
{
    use Queueable;

    private const EVENTS = [
        'supplemental_requirement',
        'document_rejected',
        'reverification_required',
        'expiration_warning',
    ];

    public function __construct(
        private readonly Connector $connector,
        private readonly string $event,
        array $requirementLabels = [],
        private readonly string $audience = 'learner',
    ) {
        if (! in_array($this->event, self::EVENTS, true)) {
            throw new InvalidArgumentException('Unsupported connector verification action-required event.');
        }

        if (! in_array($this->audience, ['learner', 'admin'], true)) {
            throw new InvalidArgumentException('Unsupported connector verification action-required audience.');
        }

        if ($this->audience === 'admin' && ! in_array($this->event, [
            'supplemental_requirement',
            'reverification_required',
        ], true)) {
            throw new InvalidArgumentException('This connector verification event is not available to administrators.');
        }

        $this->requirementLabels = array_values(array_unique(array_filter(
            array_map(static fn ($label): string => trim((string) $label), $requirementLabels),
            static fn (string $label): bool => $label !== '',
        )));
    }

    private readonly array $requirementLabels;

    public function via(object $notifiable): array
    {
        return $this->audience === 'admin'
            ? ['database']
            : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $copy = $this->copy();

        return (new MailMessage)
            ->mailer((string) config('mail.verification_mailer', config('mail.default')))
            ->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->subject($copy['title'])
            ->view('emails.moderation-status', [
                'title' => $copy['title'],
                'subtitle' => $copy['subtitle'],
                'greetingName' => $notifiable->first_name ?? 'Connector representative',
                'intro' => $copy['message'],
                'details' => array_map(
                    static fn (string $label): string => 'Evidence item: '.$label,
                    $this->requirementLabels,
                ),
                'actionUrl' => route('connector.status', $this->connector),
                'actionText' => 'Review connector status',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'type' => 'connector_verification_'.$this->event,
            'event' => $this->event,
            'title' => $copy['title'],
            'message' => $copy['message'],
            'connector_id' => $this->connector->id,
            'connector_name' => $this->connector->name,
            'status' => $this->connector->verification_status,
            'requirement_labels' => $this->requirementLabels,
            'action_url' => $this->audience === 'admin'
                ? route('admin.connectors.show', $this->connector)
                : route('connector.status', $this->connector),
            'severity' => $copy['severity'],
        ];
    }

    private function copy(): array
    {
        return match ($this->event) {
            'supplemental_requirement' => [
                'title' => 'Additional connector evidence required',
                'subtitle' => 'Please provide the requested evidence',
                'message' => 'Additional verification evidence is required for '.$this->connector->name.'.',
                'severity' => 'warning',
            ],
            'document_rejected' => [
                'title' => 'Connector evidence needs an update',
                'subtitle' => 'Please replace the rejected evidence',
                'message' => 'One or more verification evidence items for '.$this->connector->name.' were not accepted. Review the requested updates and resubmit.',
                'severity' => 'error',
            ],
            'reverification_required' => [
                'title' => 'Connector reverification required',
                'subtitle' => 'Updated evidence is required',
                'message' => 'Updated verification evidence is required for '.$this->connector->name.'.',
                'severity' => 'warning',
            ],
            'expiration_warning' => [
                'title' => 'Connector evidence expiry reminder',
                'subtitle' => 'Evidence will expire soon',
                'message' => 'One or more verification evidence items for '.$this->connector->name.' will expire soon.',
                'severity' => 'warning',
            ],
        };
    }
}
