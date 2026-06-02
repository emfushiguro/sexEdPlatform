<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\ConnectorMembership;
use App\Models\User;

class ConnectorAccessService
{
    public function activeMembership(User $user, Connector $connector): ?ConnectorMembership
    {
        return $connector->memberships()
            ->with('role.permissions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    public function membership(User $user, Connector $connector): ?ConnectorMembership
    {
        return $connector->memberships()
            ->with('role.permissions')
            ->where('user_id', $user->id)
            ->first();
    }

    public function canAccessWorkspace(User $user, Connector $connector): bool
    {
        return $connector->status === 'verified'
            && $this->activeMembership($user, $connector) !== null;
    }

    public function hasPermission(User $user, Connector $connector, string $permissionKey): bool
    {
        $membership = $this->activeMembership($user, $connector);

        if (! $membership || $connector->status !== 'verified') {
            return false;
        }

        return $membership->role?->permissions
            ->contains('permission_key', $permissionKey) ?? false;
    }

    public function abortUnlessWorkspace(User $user, Connector $connector): ConnectorMembership
    {
        $membership = $this->activeMembership($user, $connector);

        abort_if($connector->status !== 'verified', 403);
        abort_unless($membership, 403);

        return $membership;
    }

    public function abortUnlessPermission(User $user, Connector $connector, string $permissionKey): ConnectorMembership
    {
        $membership = $this->abortUnlessWorkspace($user, $connector);
        abort_unless($this->hasPermission($user, $connector, $permissionKey), 403);

        return $membership;
    }
}
