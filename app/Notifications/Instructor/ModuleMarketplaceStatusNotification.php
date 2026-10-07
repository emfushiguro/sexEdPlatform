<?php

namespace App\Notifications\Instructor;

use App\Models\ModuleMarketplaceListing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModuleMarketplaceStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ModuleMarketplaceListing $listing,
        public readonly string $status,
        public readonly ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'module_marketplace_status',
            'status' => $this->status,
            'title' => 'Connector marketplace listing suspended',
            'message' => 'Your Connector marketplace listing was suspended by an administrator.',
            'module_marketplace_listing_id' => $this->listing->id,
            'module_id' => $this->listing->module_id,
            'reason' => $this->reason,
            'url' => route('instructor.modules.show', $this->listing->module_id),
        ];
    }
}
