<?php

namespace App\Notifications\Learner;

use App\Models\Module;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModulePurchaseResultNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Module $module,
        private readonly string $status,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isSuccess = $this->status === 'success';

        return [
            'type' => 'module_purchase_result',
            'status' => $this->status,
            'title' => $isSuccess ? 'Module Purchase Successful' : 'Module Purchase Failed',
            'message' => $isSuccess
                ? 'Payment for "' . $this->module->title . '" was confirmed. You can now access this module.'
                : 'Payment for "' . $this->module->title . '" was cancelled or failed. Please try again.',
            'module_id' => $this->module->id,
            'module_title' => $this->module->title,
            'action_url' => $isSuccess
                ? route('learner.modules.show', $this->module)
                : route('learner.modules.purchase.form', $this->module),
            'severity' => $isSuccess ? 'success' : 'error',
        ];
    }
}
