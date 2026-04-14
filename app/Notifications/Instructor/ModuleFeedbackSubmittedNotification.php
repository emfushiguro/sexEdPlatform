<?php

namespace App\Notifications\Instructor;

use App\Models\ModuleFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModuleFeedbackSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ModuleFeedback $feedback,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $module = $this->feedback->module;
        $learner = $this->feedback->learner;

        return [
            'type' => 'module_feedback_submitted',
            'status' => 'new',
            'title' => 'New Module Review Received',
            'message' => ($learner?->name ?? 'A learner') . ' left a ' . (int) $this->feedback->rating . '-heart review on "' . ($module?->title ?? 'your module') . '".',
            'module_id' => $module?->id,
            'module_title' => $module?->title,
            'feedback_id' => $this->feedback->id,
            'action_url' => $module ? route('instructor.modules.show', $module) : route('instructor.modules.index'),
            'severity' => 'info',
        ];
    }
}
