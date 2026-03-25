<?php

namespace App\Notifications;

use App\Models\InstructorApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorApplicationRejected extends Notification
{
    use Queueable;

    public function __construct(private readonly InstructorApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'instructor_application_rejected',
            'title' => 'Update on Your Instructor Application',
            'message' => 'Your application was not approved. You can reapply after addressing the feedback.',
            'application_id' => $this->application->id,
            'rejection_reason' => $this->application->rejection_reason,
            'url' => route('learner.dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on Your Instructor Application')
            ->line('Thank you for applying to become an instructor.')
            ->line('Your application was not approved at this time.')
            ->line('Reason: ' . ($this->application->rejection_reason ?: 'No reason provided.'))
            ->line('You may reapply after addressing the feedback.')
            ->action('Back to Dashboard', route('learner.dashboard'));
    }
}
