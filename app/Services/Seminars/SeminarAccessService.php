<?php

namespace App\Services\Seminars;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;

class SeminarAccessService
{
    public function __construct(private readonly ConnectorAccessService $connectorAccess)
    {
    }

    public function canManageConnectorSeminars(User $user, Connector $connector): bool
    {
        return $this->connectorAccess->hasPermission($user, $connector, 'connector.manage_seminars');
    }

    public function abortUnlessCanManageConnectorSeminars(User $user, Connector $connector): void
    {
        abort_unless($this->canManageConnectorSeminars($user, $connector), 403);
    }

    public function abortUnlessConnectorOwnsSeminar(Connector $connector, Seminar $seminar): void
    {
        abort_unless((int) $seminar->connector_id === (int) $connector->id, 404);
    }

    public function activeRegistrantCount(Seminar $seminar): int
    {
        return $seminar->registrants()
            ->where('status', 'registered')
            ->whereNull('cancelled_at')
            ->count();
    }
}
