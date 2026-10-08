<?php

namespace App\Notifications;

use App\Models\PlatformFeedbackMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PlatformFeedbackMessageNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly PlatformFeedbackMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $ticket = $this->message->ticket;
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole('admin');

        return [
            'title' => 'New support ticket message',
            'message' => "A new message was added to ticket {$ticket->reference_number}.",
            'feedback_id' => $ticket->id,
            'url' => $isAdmin
                ? route('admin.feedback.show', $ticket)
                : route('feedback.show', $ticket),
        ];
    }
}
