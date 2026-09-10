<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuardianSupportAccessChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $dependent,
        private readonly bool $enabled,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'guardian_support_access_changed',
            'title' => $this->enabled ? 'Support access enabled' : 'Support access disabled',
            'message' => $this->dependent->name.($this->enabled
                ? ' allowed you to manage their optional Health & Support Information.'
                : ' removed your access to their optional Health & Support Information.'),
            'dependent_user_id' => $this->dependent->id,
            'enabled' => $this->enabled,
            'action_url' => $this->enabled
                ? route('parent.children.support-information.edit', $this->dependent)
                : route('parent.children.index'),
            'severity' => 'info',
        ];
    }
}
