<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\User;

class ConnectorRegistrationService
{
    public function __construct(private readonly ConnectorVerificationService $verification) {}

    public function register(User $user, array $data): Connector
    {
        return $this->verification->registerInitialApplication($user, $data);
    }
}
