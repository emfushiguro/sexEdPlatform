<?php

namespace App\Services\Moderation;

use App\Enums\EnforcementActionType;
use App\Models\EnforcementAction;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SuspensionService
{
    public function createFromEnforcementAction(EnforcementAction $enforcementAction, ?User $admin = null): UserSuspension
    {
        $this->guardSuspensionActionType($enforcementAction);

        return DB::transaction(function () use ($enforcementAction, $admin): UserSuspension {
            $suspension = UserSuspension::query()->create([
                'user_id' => $enforcementAction->user_id,
                'enforcement_action_id' => $enforcementAction->id,
                'moderation_case_id' => $enforcementAction->moderation_case_id,
                'status' => 'active',
                'starts_at' => $enforcementAction->starts_at ?? now(),
                'ends_at' => $this->resolveEndsAt($enforcementAction),
                'appeal_status' => 'none',
                'created_by_admin_id' => $admin?->id,
            ]);

            $this->syncUserStatus($suspension->user()->first());

            return $suspension;
        });
    }

    public function refreshState(UserSuspension $suspension): UserSuspension
    {
        if ($suspension->status !== 'active') {
            return $suspension;
        }

        if ($suspension->ends_at && $suspension->ends_at->isPast()) {
            $suspension->forceFill([
                'status' => 'expired',
            ])->save();

            $this->syncUserStatus($suspension->user()->first());
        }

        return $suspension->fresh();
    }

    public function revoke(UserSuspension $suspension, User $admin, string $reason): UserSuspension
    {
        if ($suspension->status !== 'active') {
            throw new InvalidArgumentException('Only active suspensions can be revoked.');
        }

        $suspension->forceFill([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by_admin_id' => $admin->id,
            'revoked_reason' => $reason,
        ])->save();

        $this->syncUserStatus($suspension->user()->first());

        return $suspension->fresh();
    }

    public function markAppealPending(UserSuspension $suspension): UserSuspension
    {
        if ($suspension->status !== 'active') {
            throw new InvalidArgumentException('Appeals can only be submitted for active suspensions.');
        }

        $suspension->forceFill([
            'appeal_status' => 'appeal_pending',
            'appeal_submitted_at' => now(),
        ])->save();

        return $suspension->fresh();
    }

    private function guardSuspensionActionType(EnforcementAction $enforcementAction): void
    {
        $actionType = $enforcementAction->action_type instanceof EnforcementActionType
            ? $enforcementAction->action_type
            : EnforcementActionType::from((string) $enforcementAction->action_type);

        if (!in_array($actionType, [
            EnforcementActionType::TemporarySuspension,
            EnforcementActionType::ExtendedSuspension,
            EnforcementActionType::PermanentSuspension,
        ], true)) {
            throw new InvalidArgumentException('Only suspension enforcement actions can create user suspension records.');
        }
    }

    private function resolveEndsAt(EnforcementAction $enforcementAction): ?\Illuminate\Support\Carbon
    {
        $actionType = $enforcementAction->action_type instanceof EnforcementActionType
            ? $enforcementAction->action_type
            : EnforcementActionType::from((string) $enforcementAction->action_type);

        if ($actionType === EnforcementActionType::PermanentSuspension) {
            return null;
        }

        return $enforcementAction->ends_at;
    }

    private function syncUserStatus(?User $user): void
    {
        if (!$user) {
            return;
        }

        $hasActiveSuspension = UserSuspension::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        $nextStatus = $hasActiveSuspension ? User::STATUS_SUSPENDED : User::STATUS_ACTIVE;
        if ($user->status !== $nextStatus) {
            $user->forceFill(['status' => $nextStatus])->save();
        }
    }
}
