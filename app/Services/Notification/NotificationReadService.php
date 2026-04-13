<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationReadService
{
    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function markAllReadOnDropdownOpen(User $user): int
    {
        return $this->markAllRead($user);
    }

    public function markOneRead(User $user, string $notificationId): DatabaseNotification
    {
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($notificationId);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $notification->fresh();
    }
}
