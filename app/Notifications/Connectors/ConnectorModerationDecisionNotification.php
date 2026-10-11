<?php

namespace App\Notifications\Connectors;

use App\Models\Connector;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class ConnectorModerationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Connector $connector,
        private readonly string $decision,
    ) {
        if (! in_array($this->decision, ['verified', 'rejected'], true)) {
            throw new InvalidArgumentException('Unsupported connector moderation decision.');
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->decision === 'verified';
        $title = $approved ? 'Connector application approved' : 'Connector application update';

        return (new MailMessage)
            ->mailer((string) config('mail.verification_mailer', config('mail.default')))
            ->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->subject($title)
            ->view('emails.moderation-status', [
                'title' => $title,
                'subtitle' => $approved ? 'Your verification review is complete' : 'Your verification review needs no further action',
                'greetingName' => $notifiable->first_name ?? 'Connector representative',
                'intro' => $approved
                    ? 'Your connector application for '.$this->connector->name.' has been approved.'
                    : 'Your connector application for '.$this->connector->name.' was permanently rejected.',
                'details' => $approved
                    ? ['The connector is now ready to manage.']
                    : ['Review the status page for the recorded decision.'],
                'actionUrl' => route('connector.status', $this->connector),
                'actionText' => 'View connector status',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->decision === 'verified';

        return [
            'type' => $approved ? 'connector_approved' : 'connector_rejected',
            'event' => $approved ? 'approved' : 'permanently_rejected',
            'title' => $approved ? 'Connector approved' : 'Connector rejected',
            'message' => $approved
                ? $this->connector->name.' has been approved and is ready to manage.'
                : $this->connector->name.' was rejected. Review the status page for details.',
            'connector_id' => $this->connector->id,
            'connector_name' => $this->connector->name,
            'status' => $this->decision,
            'action_url' => route('connector.status', $this->connector),
            'severity' => $approved ? 'success' : 'error',
        ];
    }
}
