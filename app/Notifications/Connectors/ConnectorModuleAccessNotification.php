<?php

namespace App\Notifications\Connectors;

use App\Models\ConnectorModuleSeatAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConnectorModuleAccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ConnectorModuleSeatAssignment $assignment,
        public readonly string $action,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->assignment->loadMissing('entitlement');

        return [
            'type' => 'connector_module_access',
            'action' => $this->action,
            'title' => match ($this->action) {
                'granted' => 'Connector module access granted',
                'revoked' => 'Connector module access revoked',
                default => 'Connector module access released',
            },
            'message' => match ($this->action) {
                'granted' => 'You can now learn this Connector-provided module.',
                'revoked' => 'Your Connector-provided module access was revoked.',
                default => 'Your Connector-provided module access was released.',
            },
            'assignment_id' => $this->assignment->id,
            'entitlement_id' => $this->assignment->connector_module_entitlement_id,
            'module_id' => $this->assignment->entitlement?->module_id,
            'connector_id' => $this->assignment->entitlement?->connector_id,
        ];
    }
}
