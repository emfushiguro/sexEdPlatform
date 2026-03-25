<?php

namespace App\Notifications;

use App\Models\InstructorApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorApplicationApproved extends Notification
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
            'type' => 'instructor_application_approved',
            'title' => 'Your instructor application was approved!',
            'message' => 'You now have access to the instructor panel.',
            'application_id' => $this->application->id,
            'url' => route('instructor.dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Instructor Application Was Approved!')
            ->greeting('Congratulations!')
            ->line('Your application to become an instructor has been approved.')
            ->line('You can now access the instructor panel and start creating content.')
            ->action('Open Instructor Dashboard', route('instructor.dashboard'))
            ->line('You now have instructor access and your previous learner records are preserved.');
    }
}
