<?php

namespace App\Notifications\Admin;

use App\Models\ModuleReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewModuleSubmissionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ModuleReviewRequest $reviewRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $module = $this->reviewRequest->module;

        return [
            'type' => 'new_module_submission',
            'status' => 'submitted',
            'title' => 'New Module Submission',
            'message' => '"' . ($module?->title ?? 'Module') . '" was submitted and is waiting for review to start.',
            'review_request_id' => $this->reviewRequest->id,
            'module_id' => $module?->id,
            'module_title' => $module?->title,
            'action_url' => route('admin.content-reviews.index', ['focus' => $this->reviewRequest->id]),
            'severity' => 'info',
        ];
    }
}
