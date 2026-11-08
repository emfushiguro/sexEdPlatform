<?php

namespace App\Services\Chat;

use App\Models\Connector;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;
use Illuminate\Database\Eloquent\Builder;

class ConnectorConversationService
{
    public function __construct(private readonly ConnectorAccessService $access) {}

    public function instructors(): Builder
    {
        return User::query()->where('status', User::STATUS_ACTIVE)
            ->where(fn (Builder $query) => $query->where('role', 'instructor')
                ->orWhereHas('roles', fn (Builder $roles) => $roles->where('name', 'instructor')));
    }

    public function visibleTo(User $viewer): Builder
    {
        return Conversation::query()
            ->where('conversation_type', Conversation::TYPE_CONNECTOR_NEGOTIATION)
            ->where(fn (Builder $query) => $query->where('participant_one_id', $viewer->id)
                ->orWhere('participant_two_id', $viewer->id))
            ->whereHas('connector', fn (Builder $query) => $query->where('status', 'verified'))
            ->whereHas('participantOne', fn (Builder $query) => $query->where('status', User::STATUS_ACTIVE)
                ->whereHas('connectorMemberships', fn (Builder $memberships) => $memberships
                    ->whereColumn('connector_memberships.connector_id', 'conversations.connector_id')
                    ->where('status', 'active')
                    ->whereHas('role.permissions', fn (Builder $permissions) => $permissions
                        ->whereIn('permission_key', $this->access->effectivePermissionKeys('connector.manage_educators')))))
            ->whereHas('participantTwo', fn (Builder $query) => $query->whereIn('id', $this->instructors()->select('users.id')));
    }

    public function start(User $organizer, Connector $connector, User $instructor): Conversation
    {
        $this->access->abortUnlessPermission($organizer, $connector, 'connector.manage_educators');
        abort_unless($organizer->status === User::STATUS_ACTIVE && $organizer->id !== $instructor->id, 403);
        abort_unless($this->instructors()->whereKey($instructor->id)->exists(), 403);

        // Negotiation participants are ordered by responsibility: organizer, then instructor.
        return Conversation::query()->firstOrCreate([
            'pair_key' => Conversation::makePairKey($organizer->id, $instructor->id),
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_CONNECTOR_NEGOTIATION, $connector->id).':'.$organizer->id,
        ], [
            'participant_one_id' => $organizer->id,
            'participant_two_id' => $instructor->id,
            'connector_id' => $connector->id,
            'conversation_type' => Conversation::TYPE_CONNECTOR_NEGOTIATION,
            'status' => Conversation::STATUS_ACTIVE,
        ]);
    }
}
