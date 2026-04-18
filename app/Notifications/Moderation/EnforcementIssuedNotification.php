<?php

namespace App\Notifications\Moderation;

use App\Models\EnforcementAction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnforcementIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly EnforcementAction $enforcementAction)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionType = $this->normalizeEnumValue($this->enforcementAction->action_type);
        $severity = $this->normalizeEnumValue($this->enforcementAction->severity_level);

        return (new MailMessage)
            ->subject('Moderation Enforcement Issued')
            ->line('A moderation enforcement action has been issued on your account.')
            ->line('Action: ' . str_replace('_', ' ', $actionType))
            ->line('Severity: ' . ucfirst($severity))
            ->line('Please review your account status for details.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'moderation_enforcement_issued',
            'enforcement_action_id' => $this->enforcementAction->id,
            'user_id' => $this->enforcementAction->user_id,
            'moderation_case_id' => $this->enforcementAction->moderation_case_id,
            'action_type' => $this->normalizeEnumValue($this->enforcementAction->action_type),
            'severity_level' => $this->normalizeEnumValue($this->enforcementAction->severity_level),
            'trigger_type' => (string) $this->enforcementAction->trigger_type,
            'starts_at' => optional($this->enforcementAction->starts_at)?->toDateTimeString(),
            'ends_at' => optional($this->enforcementAction->ends_at)?->toDateTimeString(),
            'notes' => $this->enforcementAction->notes,
        ];
    }

    private function normalizeEnumValue(mixed $value): string
    {
        if (is_object($value) && property_exists($value, 'value')) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
