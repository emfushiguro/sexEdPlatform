<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\ConnectorInvitation;
use App\Models\ConnectorMembership;
use App\Models\ConnectorRole;
use App\Models\User;
use App\Notifications\Connectors\ConnectorInvitationReceivedNotification;
use App\Notifications\Connectors\ConnectorInvitationRespondedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectorInvitationService
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorRoleService $roles,
    ) {
    }

    public function invite(Connector $connector, User $actor, User $target, ConnectorRole $role): ConnectorInvitation
    {
        if (! $this->access->hasPermission($actor, $connector, 'connector.invite_members')) {
            throw ValidationException::withMessages(['email' => 'You are not allowed to invite connector members.']);
        }

        if ($role->connector_id !== $connector->id) {
            throw ValidationException::withMessages(['connector_role_id' => 'The selected role does not belong to this connector.']);
        }

        if ($connector->memberships()->where('user_id', $target->id)->whereIn('status', ['pending', 'active'])->exists()) {
            throw ValidationException::withMessages(['email' => 'This user is already a connector member.']);
        }

        $invitation = $connector->invitations()->create([
            'connector_role_id' => $role->id,
            'invited_user_id' => $target->id,
            'invited_by' => $actor->id,
            'email' => $target->email,
            'status' => 'pending',
            'expires_at' => now()->addDays(14),
        ]);

        $target->notify(new ConnectorInvitationReceivedNotification(
            $invitation->load(['connector', 'inviter'])
        ));

        return $invitation;
    }

    public function accept(ConnectorInvitation $invitation, User $user): ConnectorMembership
    {
        if ($invitation->invited_user_id !== $user->id || $invitation->status !== 'pending') {
            throw ValidationException::withMessages(['invitation' => 'This invitation cannot be accepted.']);
        }

        return DB::transaction(function () use ($invitation, $user) {
            $membership = ConnectorMembership::updateOrCreate(
                [
                    'connector_id' => $invitation->connector_id,
                    'user_id' => $user->id,
                ],
                [
                    'connector_role_id' => $invitation->connector_role_id,
                    'status' => 'active',
                    'accepted_at' => now(),
                    'removed_at' => null,
                ]
            );

            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $invitation->inviter?->notify(new ConnectorInvitationRespondedNotification(
                $invitation->load(['connector', 'invitedUser'])
            ));

            return $membership;
        });
    }

    public function reject(ConnectorInvitation $invitation, User $user): ConnectorInvitation
    {
        if ($invitation->invited_user_id !== $user->id || $invitation->status !== 'pending') {
            throw ValidationException::withMessages(['invitation' => 'This invitation cannot be rejected.']);
        }

        $invitation->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        $invitation->inviter?->notify(new ConnectorInvitationRespondedNotification(
            $invitation->load(['connector', 'invitedUser'])
        ));

        return $invitation;
    }

    public function resend(ConnectorInvitation $invitation, User $actor): ConnectorInvitation
    {
        if (! $this->access->hasPermission($actor, $invitation->connector, 'connector.invite_members')) {
            throw ValidationException::withMessages(['invitation' => 'You are not allowed to resend connector invitations.']);
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages(['invitation' => 'Only pending invitations can be resent.']);
        }

        $invitation->update(['expires_at' => now()->addDays(14)]);

        $invitation->invitedUser?->notify(new ConnectorInvitationReceivedNotification(
            $invitation->load(['connector', 'inviter'])
        ));

        return $invitation;
    }

    public function remove(ConnectorMembership $membership): void
    {
        $this->roles->assertCanRemoveMembership($membership->loadMissing('role'));

        $membership->update([
            'status' => 'removed',
            'removed_at' => now(),
        ]);
    }
}
