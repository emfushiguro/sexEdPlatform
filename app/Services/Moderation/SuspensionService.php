<?php

namespace App\Services\Moderation;

use App\Enums\EnforcementActionType;
use App\Models\EnforcementAction;
use App\Models\User;
use App\Models\UserSuspension;
use App\Notifications\Moderation\SuspensionIssuedNotification;
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

            $user = User::query()->find($suspension->user_id);

            $this->syncUserStatus($user);

            DB::afterCommit(function () use ($user, $admin, $suspension): void {
                $user?->notify(new SuspensionIssuedNotification($suspension));
                $admin?->notify(new \App\Notifications\Moderation\AdminSuspensionRecordedNotification($suspension));
            });

            return $suspension;
        });
    }

    public function changeDuration(UserSuspension $suspension, User $admin, int $durationAmount, string $durationUnit, string $reason): UserSuspension
    {
        abort_unless($admin->isAdmin(), 403);
        validator(['duration_amount' => $durationAmount, 'duration_unit' => $durationUnit, 'reason' => $reason], [
            'duration_amount' => ['integer', 'min:1', 'max:'.($durationUnit === 'hours' ? 8760 : 365)],
            'duration_unit' => ['in:hours,days'], 'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($suspension, $admin, $durationAmount, $durationUnit, $reason): UserSuspension {
            $case = \App\Models\ModerationCase::whereKey($suspension->moderation_case_id)->lockForUpdate()->first();
            $locked = UserSuspension::whereKey($suspension->id)->lockForUpdate()->firstOrFail();
            $action = EnforcementAction::whereKey($locked->enforcement_action_id)->lockForUpdate()->first();
            $valid = $case && $case->case_source === \App\Enums\ModerationCaseSource::ChatReport
                && $case->content_type === 'message_report' && $locked->status === 'active'
                && $locked->ends_at?->isFuture() && $locked->starts_at && $action
                && $action->action_type === EnforcementActionType::TemporarySuspension
                && $action->status === 'executed' && $action->ends_at
                && $action->starts_at && $action->starts_at->equalTo($locked->starts_at)
                && (int) $action->user_id === (int) $locked->user_id
                && (int) $action->moderation_case_id === (int) $case->id
                && (int) $locked->moderation_case_id === (int) $case->id;
            if (! $valid) {
                throw \Illuminate\Validation\ValidationException::withMessages(['duration_amount' => 'Only an active temporary chat-report suspension with a matching enforcement record can be changed.']);
            }
            $end = $locked->starts_at->copy()->add($durationUnit, $durationAmount);
            if (! $end->isFuture()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['duration_amount' => 'The total duration must produce an end time in the future.']);
            }
            if ($locked->ends_at->equalTo($end) && $action->ends_at->equalTo($end)) {
                return $locked;
            }

            $before = ['starts_at' => $locked->starts_at->toDateTimeString(), 'ends_at' => $locked->ends_at->toDateTimeString()];
            $metadata = $case->metadata ?? [];
            $selection = $metadata['chat_temporary_suspension'] ?? [];
            $originalSeconds = $locked->starts_at->diffInSeconds($locked->ends_at);
            $selection['initial'] ??= ['amount' => $originalSeconds > 0 && fmod($originalSeconds, 3600) === 0.0 ? (int) ($originalSeconds / 3600) : null, 'unit' => 'hours'];
            $selection['current'] = ['amount' => $durationAmount, 'unit' => $durationUnit];
            $selection['user_suspension_id'] = $locked->id;
            $selection['enforcement_action_id'] = $action->id;
            $metadata['chat_temporary_suspension'] = $selection;
            // Older MySQL schemas auto-update their first TIMESTAMP unless it is explicitly assigned.
            DB::table($locked->getTable())->where('id', $locked->id)->update([
                'starts_at' => $locked->starts_at, 'ends_at' => $end, 'updated_at' => now(),
            ]);
            DB::table($action->getTable())->where('id', $action->id)->update([
                'starts_at' => $action->starts_at, 'ends_at' => $end, 'updated_at' => now(),
            ]);
            $locked->refresh();
            $case->update(['metadata' => $metadata]);
            app(\App\Services\AdminActivityLogService::class)->logModelMutation(
                action: 'moderation.suspension.duration_changed', entity: $locked,
                before: $before, after: ['starts_at' => $before['starts_at'], 'ends_at' => $end->toDateTimeString()],
                meta: ['reason' => trim($reason), 'moderation_case_id' => $case->id, 'enforcement_action_id' => $action->id], adminUserId: $admin->id,
            );
            DB::afterCommit(function () use ($locked, $admin): void {
                $locked->user?->notify(new \App\Notifications\Moderation\SuspensionDurationChangedNotification($locked));
                $admin->notify(new \App\Notifications\Moderation\AdminSuspensionRecordedNotification($locked, 'duration_changed'));
            });

            return $locked->fresh();
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

    public function refreshExpiredForUser(User $user): void
    {
        $expired = UserSuspension::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);

        if ($expired > 0) {
            $this->syncUserStatus($user);
        }
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

        if (! in_array($actionType, [
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
        if (! $user) {
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
