<?php

namespace App\Notifications;

use App\Models\InstructorApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorApplicationDocumentsRequested extends Notification
{
    public function __construct(private readonly InstructorApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'instructor_application_documents_requested',
            'title' => 'Document replacements requested',
            'message' => 'Please replace the requested documents in your instructor application.',
            'url' => route('instructor-application.documents.requested', $this->application),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Please update your instructor application documents')
            ->line('An administrator has requested replacement documents for your instructor application.')
            ->action('Review requested documents', route('instructor-application.documents.requested', $this->application));
    }
}
