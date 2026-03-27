<?php

namespace App\Notifications;

use App\Models\InstructorApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorApplicationSubmitted extends Notification
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
            'type' => 'instructor_application_submitted',
            'title' => 'New Instructor Application',
            'message' => 'A learner has submitted an instructor application.',
            'application_id' => $this->application->id,
            'url' => route('admin.instructor-applications.show', $this->application),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Instructor Application Submitted')
            ->line('A learner has submitted an instructor application and it is ready for review.')
            ->action('Review Application', route('admin.instructor-applications.show', $this->application));
    }
}
