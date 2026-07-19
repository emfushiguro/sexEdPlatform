<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\ConnectorMembership;
use App\Models\ConnectorMembershipRequest;
use App\Models\ConnectorRole;
use App\Models\User;
use App\Notifications\Connectors\ConnectorMembershipRequestDecisionNotification;
use App\Notifications\Connectors\ConnectorMembershipRequestSubmittedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectorMembershipRequestService
{
    public function request(Connector $connector, User $user): ConnectorMembershipRequest
    {
        if ($connector->status !== 'verified') {
            throw ValidationException::withMessages(['connector' => 'This connector is not accepting membership requests.']);
        }

        if ($connector->memberships()->where('user_id', $user->id)->whereIn('status', ['pending', 'active'])->exists()) {
            throw ValidationException::withMessages(['connector' => 'You are already a member of this connector.']);
        }

        if ($connector->invitations()->where('invited_user_id', $user->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['connector' => 'You already have an invitation from this connector.']);
        }

        if ($connector->membershipRequests()->where('user_id', $user->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['connector' => 'Your membership request is already pending.']);
        }

        $membershipRequest = $connector->membershipRequests()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->managers($connector)->each(
            fn (User $manager) => $manager->notify(new ConnectorMembershipRequestSubmittedNotification(
                $membershipRequest->load(['connector', 'user'])
            ))
        );

        return $membershipRequest;
    }

    public function approve(ConnectorMembershipRequest $membershipRequest, User $actor): ConnectorMembership
    {
        $connector = $membershipRequest->connector;

        return DB::transaction(function () use ($membershipRequest, $actor, $connector): ConnectorMembership {
            $membership = ConnectorMembership::updateOrCreate(
                [
                    'connector_id' => $connector->id,
                    'user_id' => $membershipRequest->user_id,
                ],
                [
                    'connector_role_id' => $this->memberRole($connector)->id,
                    'status' => 'active',
                    'accepted_at' => now(),
                    'removed_at' => null,
                ]
            );

            $membershipRequest->update([
                'status' => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            $membershipRequest->user?->notify(new ConnectorMembershipRequestDecisionNotification($membershipRequest->fresh(['connector'])));

            return $membership;
        });
    }

    public function reject(ConnectorMembershipRequest $membershipRequest, User $actor, string $reason, ?string $note = null): ConnectorMembershipRequest
    {
        $membershipRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'rejection_note' => $note,
        ]);

        $membershipRequest->user?->notify(new ConnectorMembershipRequestDecisionNotification($membershipRequest->fresh(['connector'])));

        return $membershipRequest;
    }

    private function memberRole(Connector $connector): ConnectorRole
    {
        return app(ConnectorRoleService::class)->defaultMemberRole($connector);
    }

    private function managers(Connector $connector)
    {
        return User::query()
            ->whereHas('connectorMemberships', fn ($query) => $query
                ->where('connector_id', $connector->id)
                ->where('status', 'active')
                ->whereHas('role.permissions', fn ($permission) => $permission->where('permission_key', 'connector.manage_members')))
            ->get();
    }
}
