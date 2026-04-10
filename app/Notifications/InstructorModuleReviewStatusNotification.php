<?php

namespace App\Notifications;

use App\Models\ModuleReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InstructorModuleReviewStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ModuleReviewRequest $reviewRequest,
        public readonly string $status,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $actionUrl = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'module_review_status_update',
            'status' => $this->status,
            'module_id' => $this->reviewRequest->module_id,
            'module_title' => $this->reviewRequest->module_title,
            'review_request_id' => $this->reviewRequest->id,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->actionUrl ?? route('instructor.modules.show', $this->reviewRequest->module_id),
        ];
    }
}
