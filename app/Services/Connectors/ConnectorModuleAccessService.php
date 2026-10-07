<?php

namespace App\Services\Connectors;

use App\Models\ConnectorMembership;
use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\ModuleAccessGrant;
use App\Models\User;
use App\Notifications\Connectors\ConnectorModuleAccessNotification;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectorModuleAccessService
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ModuleAccessService $moduleAccess,
    ) {
    }

    public function storeRule(
        ConnectorModuleEntitlement $entitlement,
        User $actor,
        array $data,
    ): ConnectorModuleAccessRule {
        $this->authorizeEntitlement($entitlement, $actor);
        $targetType = (string) ($data['target_type'] ?? '');
        $targetId = isset($data['target_id']) ? (int) $data['target_id'] : null;
        $this->validateTarget($entitlement, $targetType, $targetId);
        $ruleKey = $this->ruleKey($targetType, $targetId);

        return DB::transaction(function () use (
            $entitlement,
            $actor,
            $targetType,
            $targetId,
            $ruleKey,
        ): ConnectorModuleAccessRule {
            $lockedEntitlement = ConnectorModuleEntitlement::query()
                ->whereKey($entitlement->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existing = ConnectorModuleAccessRule::query()
                ->where('connector_module_entitlement_id', $lockedEntitlement->id)
                ->where('rule_key', $ruleKey)
                ->lockForUpdate()
                ->first();

            if ($existing?->is_active) {
                throw ValidationException::withMessages([
                    'target_type' => 'This access rule already exists.',
                ]);
            }

            if ($this->countRuleCandidates($lockedEntitlement, $targetType, $targetId) > (int) $lockedEntitlement->seat_capacity) {
                throw ValidationException::withMessages([
                    'target_type' => 'This rule exceeds the available seat capacity.',
                ]);
            }

            $rule = $existing ?? new ConnectorModuleAccessRule();
            $rule->forceFill([
                'connector_module_entitlement_id' => $lockedEntitlement->id,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'rule_key' => $ruleKey,
                'is_active' => true,
                'created_by' => $existing?->created_by ?? $actor->id,
            ])->save();

            $this->reconcileEntitlement($lockedEntitlement);

            return $rule->fresh();
        });
    }

    public function removeRule(ConnectorModuleAccessRule $rule, User $actor): void
    {
        $rule->loadMissing('entitlement');
        $this->authorizeEntitlement($rule->entitlement, $actor);

        DB::transaction(function () use ($rule): void {
            $rule->forceFill(['is_active' => false])->save();
            $this->reconcileEntitlement($rule->entitlement);
        });
    }

    public function reconcileEntitlement(ConnectorModuleEntitlement $entitlement): void
    {
        DB::transaction(function () use ($entitlement): void {
            $lockedEntitlement = ConnectorModuleEntitlement::query()
                ->with('module', 'connector')
                ->whereKey($entitlement->id)
                ->lockForUpdate()
                ->firstOrFail();

            $rules = ConnectorModuleAccessRule::query()
                ->where('connector_module_entitlement_id', $lockedEntitlement->id)
                ->where('is_active', true)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
            $memberships = $lockedEntitlement->connector
                ->memberships()
                ->with(['user', 'role'])
                ->where('status', 'active')
                ->get();
            $assignments = ConnectorModuleSeatAssignment::query()
                ->where('connector_module_entitlement_id', $lockedEntitlement->id)
                ->get()
                ->keyBy('user_id');

            $candidates = collect();
            foreach ($memberships as $membership) {
                $matchingRule = $rules
                    ->filter(fn (ConnectorModuleAccessRule $rule): bool => $this->matches($rule, $membership))
                    ->sortBy(fn (ConnectorModuleAccessRule $rule): array => $this->rulePriority($rule, $membership))
                    ->first();

                if (!$matchingRule || !$this->isEligible($lockedEntitlement, $membership->user)) {
                    continue;
                }

                $existing = $assignments->get($membership->user_id);
                if ($existing?->status === ConnectorModuleSeatAssignment::STATUS_REVOKED) {
                    continue;
                }

                $candidates->push([
                    'membership' => $membership,
                    'rule' => $matchingRule,
                    'priority' => $this->rulePriority($matchingRule, $membership),
                ]);
            }

            $candidates = $candidates
                ->sortBy('priority')
                ->values();

            foreach ($assignments as $assignment) {
                if ($assignment->status === ConnectorModuleSeatAssignment::STATUS_REVOKED) {
                    continue;
                }

                if (!$candidates->contains(fn (array $candidate): bool => (int) $candidate['membership']->user_id === (int) $assignment->user_id)) {
                    $this->releaseAssignment($assignment, 'rule_or_membership_removed');
                }
            }

            $seatCount = (int) ConnectorModuleSeatAssignment::query()
                ->where('connector_module_entitlement_id', $lockedEntitlement->id)
                ->where('consumes_seat', true)
                ->whereNull('released_at')
                ->whereIn('status', [
                    ConnectorModuleSeatAssignment::STATUS_ACTIVE,
                    ConnectorModuleSeatAssignment::STATUS_PENDING_GUARDIAN,
                ])
                ->count();

            foreach ($candidates as $candidate) {
                /** @var ConnectorMembership $membership */
                $membership = $candidate['membership'];
                /** @var ConnectorModuleAccessRule $rule */
                $rule = $candidate['rule'];
                $assignment = $assignments->get($membership->user_id)
                    ?? new ConnectorModuleSeatAssignment([
                        'connector_module_entitlement_id' => $lockedEntitlement->id,
                        'user_id' => $membership->user_id,
                    ]);

                if ($assignment->status === ConnectorModuleSeatAssignment::STATUS_REVOKED) {
                    continue;
                }

                $hasIndependentAccess = ModuleAccessGrant::query()
                    ->where('user_id', $membership->user_id)
                    ->where('module_id', $lockedEntitlement->module_id)
                    ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
                    ->where('source_type', '!=', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)
                    ->exists();

                if ($hasIndependentAccess) {
                    $this->saveAssignment(
                        $assignment,
                        $membership,
                        $rule,
                        ConnectorModuleSeatAssignment::STATUS_INDEPENDENT,
                        false,
                        null,
                    );
                    continue;
                }

                $alreadyConsumesSeat = $assignment->exists
                    && $assignment->consumes_seat
                    && is_null($assignment->released_at)
                    && in_array($assignment->status, [
                        ConnectorModuleSeatAssignment::STATUS_ACTIVE,
                        ConnectorModuleSeatAssignment::STATUS_PENDING_GUARDIAN,
                    ], true);
                if (!$alreadyConsumesSeat && $seatCount >= (int) $lockedEntitlement->seat_capacity) {
                    $this->saveAssignment(
                        $assignment,
                        $membership,
                        $rule,
                        ConnectorModuleSeatAssignment::STATUS_INACTIVE,
                        false,
                        'capacity_unavailable',
                    );
                    continue;
                }

                $guardianPending = $this->requiresGuardian($membership->user);
                $status = $guardianPending
                    ? ConnectorModuleSeatAssignment::STATUS_PENDING_GUARDIAN
                    : ConnectorModuleSeatAssignment::STATUS_ACTIVE;
                $this->saveAssignment($assignment, $membership, $rule, $status, true, null, $guardianPending);
                if (!$alreadyConsumesSeat) {
                    $seatCount++;
                }
            }
        });
    }

    public function reconcileMember(ConnectorMembership $membership): void
    {
        $membership->loadMissing('connector');
        if ($membership->status !== 'active') {
            $this->releaseMembership($membership);
            return;
        }

        ConnectorModuleEntitlement::query()
            ->where('connector_id', $membership->connector_id)
            ->whereIn('status', [
                ConnectorModuleEntitlement::STATUS_ACTIVE,
                ConnectorModuleEntitlement::STATUS_CORRECTED,
            ])
            ->get()
            ->each(fn (ConnectorModuleEntitlement $entitlement) => $this->reconcileEntitlement($entitlement));
    }

    public function releaseMembership(
        ConnectorMembership $membership,
        string $reason = 'membership_removed',
    ): void {
        ConnectorModuleSeatAssignment::query()
            ->where('connector_membership_id', $membership->id)
            ->where('status', '!=', ConnectorModuleSeatAssignment::STATUS_REVOKED)
            ->get()
            ->each(fn (ConnectorModuleSeatAssignment $assignment) => $this->releaseAssignment($assignment, $reason));
    }

    public function releaseEntitlement(
        ConnectorModuleEntitlement $entitlement,
        string $reason = 'entitlement_released',
    ): void {
        ConnectorModuleSeatAssignment::query()
            ->where('connector_module_entitlement_id', $entitlement->id)
            ->where('status', '!=', ConnectorModuleSeatAssignment::STATUS_REVOKED)
            ->get()
            ->each(fn (ConnectorModuleSeatAssignment $assignment) => $this->releaseAssignment($assignment, $reason));
    }

    public function revoke(
        ConnectorModuleEntitlement $entitlement,
        User $learner,
        User $actor,
        string $reason,
    ): ConnectorModuleSeatAssignment {
        $this->authorizeEntitlement($entitlement, $actor);

        return DB::transaction(function () use ($entitlement, $learner, $actor, $reason): ConnectorModuleSeatAssignment {
            $assignment = ConnectorModuleSeatAssignment::query()->firstOrNew([
                'connector_module_entitlement_id' => $entitlement->id,
                'user_id' => $learner->id,
            ]);
            $previousStatus = $assignment->exists ? $assignment->status : null;
            $assignment->forceFill([
                'connector_membership_id' => $assignment->connector_membership_id
                    ?? $entitlement->connector->memberships()->where('user_id', $learner->id)->value('id'),
                'source_type' => $assignment->source_type ?? ConnectorModuleSeatAssignment::SOURCE_DIRECT,
                'status' => ConnectorModuleSeatAssignment::STATUS_REVOKED,
                'inactive_reason' => $reason,
                'consumes_seat' => false,
                'released_at' => $assignment->released_at ?? now(),
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
            ])->save();

            $this->moduleAccess->deactivateSource(
                ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
                (int) $assignment->id,
                $reason,
            );

            if ($previousStatus !== ConnectorModuleSeatAssignment::STATUS_REVOKED) {
                $this->queueAssignmentNotification($assignment, 'revoked');
            }

            return $assignment->fresh();
        });
    }

    public function clearRevocation(
        ConnectorModuleEntitlement $entitlement,
        User $learner,
        User $actor,
    ): ConnectorModuleSeatAssignment {
        $this->authorizeEntitlement($entitlement, $actor);
        $assignment = ConnectorModuleSeatAssignment::query()
            ->where('connector_module_entitlement_id', $entitlement->id)
            ->where('user_id', $learner->id)
            ->firstOrFail();

        $assignment->forceFill([
            'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
            'inactive_reason' => 'revocation_cleared',
            'consumes_seat' => false,
            'released_at' => now(),
            'revoked_at' => null,
            'revoked_by' => null,
        ])->save();
        $this->reconcileEntitlement($entitlement);

        return $assignment->fresh();
    }

    private function authorizeEntitlement(ConnectorModuleEntitlement $entitlement, User $actor): void
    {
        $entitlement->loadMissing('connector');
        $this->access->abortUnlessPermission($actor, $entitlement->connector, 'connector.manage_modules');
    }

    private function validateTarget(
        ConnectorModuleEntitlement $entitlement,
        string $targetType,
        ?int $targetId,
    ): void {
        if (!in_array($targetType, [
            ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            ConnectorModuleAccessRule::TARGET_ROLE,
            ConnectorModuleAccessRule::TARGET_MEMBER,
        ], true)) {
            throw ValidationException::withMessages(['target_type' => 'Choose a valid access target.']);
        }

        if ($targetType === ConnectorModuleAccessRule::TARGET_ALL_MEMBERS && $targetId !== null) {
            throw ValidationException::withMessages(['target_id' => 'All-member rules do not accept a target id.']);
        }

        if ($targetType === ConnectorModuleAccessRule::TARGET_ROLE) {
            $belongs = $targetId !== null && $entitlement->connector->roles()->whereKey($targetId)->exists();
            if (!$belongs) {
                throw ValidationException::withMessages(['target_id' => 'That role does not belong to this Connector.']);
            }
        }

        if ($targetType === ConnectorModuleAccessRule::TARGET_MEMBER) {
            $belongs = $targetId !== null && $entitlement->connector->memberships()
                ->where('user_id', $targetId)
                ->exists();
            if (!$belongs) {
                throw ValidationException::withMessages(['target_id' => 'That member does not belong to this Connector.']);
            }
        }
    }

    private function countRuleCandidates(
        ConnectorModuleEntitlement $entitlement,
        string $targetType,
        ?int $targetId,
    ): int {
        $entitlement->loadMissing('connector', 'module');

        return $entitlement->connector->memberships()
            ->with('user', 'role')
            ->where('status', 'active')
            ->get()
            ->filter(fn (ConnectorMembership $membership): bool =>
                $this->matchesTarget($targetType, $targetId, $membership)
                && $this->isEligible($entitlement, $membership->user)
            )
            ->count();
    }

    private function matches(ConnectorModuleAccessRule $rule, ConnectorMembership $membership): bool
    {
        return $this->matchesTarget($rule->target_type, $rule->target_id, $membership);
    }

    private function matchesTarget(
        string $targetType,
        ?int $targetId,
        ConnectorMembership $membership,
    ): bool {
        return match ($targetType) {
            ConnectorModuleAccessRule::TARGET_ALL_MEMBERS => true,
            ConnectorModuleAccessRule::TARGET_ROLE => (int) $membership->connector_role_id === (int) $targetId,
            ConnectorModuleAccessRule::TARGET_MEMBER => (int) $membership->user_id === (int) $targetId,
            default => false,
        };
    }

    private function rulePriority(
        ConnectorModuleAccessRule $rule,
        ConnectorMembership $membership,
    ): array {
        return [
            $rule->target_type === ConnectorModuleAccessRule::TARGET_MEMBER ? 0 : 1,
            $rule->created_at?->getTimestamp() ?? PHP_INT_MAX,
            $membership->accepted_at?->getTimestamp() ?? PHP_INT_MAX,
            (int) $membership->user_id,
        ];
    }

    private function isEligible(ConnectorModuleEntitlement $entitlement, User $user): bool
    {
        if (in_array($user->status, [
            User::STATUS_SUSPENDED,
            User::STATUS_INACTIVE,
            User::STATUS_ARCHIVED,
        ], true) || !$entitlement->module->isLearnerVisible()) {
            return false;
        }

        $age = $user->calculateAge();
        if ($age !== null && $entitlement->module->min_age !== null && $age < (int) $entitlement->module->min_age) {
            return false;
        }
        if ($age !== null && $entitlement->module->max_age !== null && $age > (int) $entitlement->module->max_age) {
            return false;
        }

        return true;
    }

    private function requiresGuardian(User $user): bool
    {
        $age = $user->calculateAge();

        return $age !== null && $age < 13;
    }

    private function saveAssignment(
        ConnectorModuleSeatAssignment $assignment,
        ConnectorMembership $membership,
        ConnectorModuleAccessRule $rule,
        string $status,
        bool $consumesSeat,
        ?string $inactiveReason,
        bool $guardianPending = false,
    ): void {
        $previousStatus = $assignment->exists ? $assignment->status : null;
        $wasInactive = $assignment->exists && $assignment->status === ConnectorModuleSeatAssignment::STATUS_INACTIVE;
        $assignment->forceFill([
            'connector_module_entitlement_id' => $rule->connector_module_entitlement_id,
            'user_id' => $membership->user_id,
            'connector_membership_id' => $membership->id,
            'connector_module_access_rule_id' => $rule->id,
            'source_type' => $rule->target_type === ConnectorModuleAccessRule::TARGET_MEMBER
                ? ConnectorModuleSeatAssignment::SOURCE_DIRECT
                : ConnectorModuleSeatAssignment::SOURCE_INHERITED,
            'status' => $status,
            'inactive_reason' => $inactiveReason,
            'consumes_seat' => $consumesSeat,
            'assigned_at' => $assignment->assigned_at ?? now(),
            'released_at' => $consumesSeat ? null : ($assignment->released_at ?? now()),
            'reactivated_at' => $wasInactive && $consumesSeat ? now() : $assignment->reactivated_at,
        ])->save();

        $this->moduleAccess->grantConnectorAssignment($assignment, $guardianPending);

        if ($previousStatus !== $status) {
            $this->queueAssignmentNotification(
                $assignment,
                in_array($status, [
                    ConnectorModuleSeatAssignment::STATUS_ACTIVE,
                    ConnectorModuleSeatAssignment::STATUS_INDEPENDENT,
                ], true) ? 'granted' : 'released',
            );
        }
    }

    private function releaseAssignment(
        ConnectorModuleSeatAssignment $assignment,
        string $reason,
    ): void {
        if ($assignment->status === ConnectorModuleSeatAssignment::STATUS_REVOKED) {
            return;
        }

        $previousStatus = $assignment->status;
        $assignment->forceFill([
            'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
            'inactive_reason' => $reason,
            'consumes_seat' => false,
            'released_at' => $assignment->released_at ?? now(),
        ])->save();
        $this->moduleAccess->deactivateSource(
            ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            (int) $assignment->id,
            $reason,
        );

        if ($previousStatus !== ConnectorModuleSeatAssignment::STATUS_INACTIVE) {
            $this->queueAssignmentNotification($assignment, 'released');
        }
    }

    private function queueAssignmentNotification(
        ConnectorModuleSeatAssignment $assignment,
        string $action,
    ): void {
        $assignmentId = (int) $assignment->id;

        DB::afterCommit(function () use ($assignmentId, $action): void {
            $resolved = ConnectorModuleSeatAssignment::query()
                ->with(['entitlement', 'user'])
                ->find($assignmentId);

            if ($resolved?->user) {
                $resolved->user->notify(new ConnectorModuleAccessNotification($resolved, $action));
            }
        });
    }

    private function ruleKey(string $targetType, ?int $targetId): string
    {
        return match ($targetType) {
            ConnectorModuleAccessRule::TARGET_ALL_MEMBERS => 'all',
            ConnectorModuleAccessRule::TARGET_ROLE => 'role:' . $targetId,
            ConnectorModuleAccessRule::TARGET_MEMBER => 'member:' . $targetId,
            default => throw ValidationException::withMessages(['target_type' => 'Choose a valid access target.']),
        };
    }
}
