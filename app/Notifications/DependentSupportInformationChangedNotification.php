<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DependentSupportInformationChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $actor,
        private readonly string $action,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $verb = match ($this->action) {
            'created' => 'added',
            'removed' => 'removed',
            default => 'updated',
        };

        return [
            'type' => 'dependent_support_information_changed',
            'title' => 'Support information '.$verb,
            'message' => $this->actor->name.' '.$verb.' your optional Health & Support Information.',
            'actor_user_id' => $this->actor->id,
            'action' => $this->action,
            'action_url' => route('learner.support-information.edit'),
            'severity' => 'info',
        ];
    }
}
