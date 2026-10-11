<?php

namespace App\Services\Support;

use App\Models\Connector;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;

class SupportLayoutResolver
{
    public function __construct(
        private readonly ConnectorAccessService $connectorAccess,
    ) {
    }

    public function resolve(?User $user, ?Connector $connector = null): string
    {
        if ($connector !== null) {
            abort_unless($user !== null, 403);
            if (! $user->hasRole('admin')) {
                $this->connectorAccess->abortUnlessWorkspace($user, $connector);
            }

            return 'layouts.connector-app';
        }

        return match ($user?->role) {
            'admin' => 'layouts.admin',
            'instructor' => 'layouts.instructor-app',
            'learner', 'parent' => 'layouts.learner-app',
            default => 'layouts.landing',
        };
    }
}
