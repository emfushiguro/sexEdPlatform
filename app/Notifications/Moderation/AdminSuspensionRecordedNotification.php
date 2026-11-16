<?php

namespace App\Notifications\Moderation;

use App\Models\UserSuspension;
use App\Support\NotificationPayloadNormalizer;
use Illuminate\Notifications\Notification;

class AdminSuspensionRecordedNotification extends Notification
{
    public function __construct(public readonly UserSuspension $suspension, public readonly string $event = 'issued') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $reference = 'Suspension #'.$this->suspension->id;
        if ($caseCode = $this->suspension->moderationCase?->case_reference_code) {
            $reference = 'Case '.$caseCode.' ('.$reference.')';
        }
        $action = $this->suspension->enforcementAction?->action_type?->value;
        $actionLabel = $action ? ucfirst(str_replace('_', ' ', $action)) : 'Account suspension';
        $subject = $this->suspension->user?->name ?? 'User #'.$this->suspension->user_id;
        $start = $this->suspension->starts_at?->copy()->timezone(config('app.timezone'))->format('M d, Y h:i A T') ?? 'an unavailable start time';
        $end = $this->suspension->ends_at?->copy()->timezone(config('app.timezone'))->format('M d, Y h:i A T');
        $event = $this->event === 'duration_changed' ? 'updated' : 'recorded';
        $payload = ['type' => 'moderation_admin_suspension_recorded', 'event' => $this->event,
            'user_suspension_id' => $this->suspension->id, 'user_id' => $this->suspension->user_id,
            'message' => $reference.': '.$actionLabel.' for '.$subject.' was '.$event.'; starts '.$start.($end ? '; ends '.$end.'.' : '; no scheduled end time.'),
            'starts_at' => $this->suspension->starts_at?->toDateTimeString(),
            'ends_at' => $this->suspension->ends_at?->toDateTimeString()];

        return [...$payload, ...app(NotificationPayloadNormalizer::class)->normalize($payload)];
    }
}
