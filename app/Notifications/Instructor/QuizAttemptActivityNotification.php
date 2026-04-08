<?php

namespace App\Notifications\Instructor;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuizAttemptActivityNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly QuizAttempt $attempt)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $quiz = $this->attempt->quiz;
        $module = $quiz?->module;
        $learner = $this->attempt->user;

        return [
            'type' => 'quiz_attempt_activity',
            'status' => 'completed',
            'title' => 'New Quiz Attempt Submitted',
            'message' => ($learner?->name ?? 'A learner') . ' scored ' . (int) $this->attempt->score . '% on "' . ($quiz?->title ?? 'Quiz') . '".',
            'quiz_attempt_id' => $this->attempt->id,
            'quiz_id' => $quiz?->id,
            'quiz_title' => $quiz?->title,
            'module_id' => $module?->id,
            'module_title' => $module?->title,
            'learner_id' => $learner?->id,
            'learner_name' => $learner?->name,
            'action_url' => route('instructor.assessments.index'),
            'severity' => 'info',
        ];
    }
}
