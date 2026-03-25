<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorApplicationStatusUpdate extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $status,
        public ?string $remarks = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->status === 'approved' 
            ? 'Congratulations! Your Instructor Application is Approved' 
            : 'Update on your Instructor Application';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.instructor-application-status', [
                'status' => $this->status,
                'remarks' => $this->remarks,
                'user' => $notifiable
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'instructor_application_status',
            'title' => $this->status === 'approved' ? 'Instructor Application Approved' : 'Instructor Application Rejected',
            'message' => $this->status === 'approved' 
                ? 'Welcome aboard! You can now access instructor features.' 
                : 'Your application was not approved. Check your email for details.',
            'status' => $this->status,
            'remarks' => $this->remarks,
            'module_url' => $this->status === 'approved' ? route('instructor.dashboard') : null,
        ];
    }
}
