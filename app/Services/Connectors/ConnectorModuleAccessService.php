<?php

namespace App\Services\Connectors;

use App\Models\ActivityLog;
use App\Models\ConnectorMembership;
use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\Module;
use App\Models\ModuleAccessGrant;
use App\Models\User;
use App\Notifications\Connectors\ConnectorModuleAccessNotification;
use App\Services\AdminActivityLogService;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConnectorModuleAccessService
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ModuleAccessService $moduleAccess,
        private readonly AdminActivityLogService $audit,
    ) {}

    public function reviewMembers(ConnectorModuleEntitlement $entitlement, User $actor, string $action, array $userIds): Collection
    {
        abort_unless($actor->hasRole('admin'), 403);

        return $this->selectedMembers($entitlement, $action, $userIds);
    }

    private function selectedMembers(ConnectorModuleEntitlement $entitlement, string $action, array $userIds): Collection
    {
        Validator::make(['action' => $action, 'user_ids' => $userIds], [
            'action' => ['required', 'in:grant,revoke'],
            'user_ids' => ['required', 'array', 'min:1', 'max:100'],
            'user_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();
        $entitlement->loadMissing('connector', 'module');

        if ($action === 'grant' && (! $entitlement->isUsable()
            || $entitlement->connector->status !== 'verified'
            || ! $entitlement->module?->isLearnerVisible())) {
            throw ValidationException::withMessages(['entitlement' => 'Choose a usable organizational license for a published module and verified Connector.']);
        }

        $memberships = $entitlement->connector->memberships()->with(['user' => fn ($query) => $query->lockForUpdate(), 'role'])
            ->lockForUpdate()
            ->whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $assignments = $entitlement->assignments()->with('user')->whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $users = collect();
        foreach ($userIds as $userId) {
            $membership = $memberships->get($userId);
            $assignment = $assignments->get($userId);
            $user = $membership?->user ?? $assignment?->user;
            if (! $user || ($action === 'grant' && ($membership?->status !== 'active' || ! $this->isEligible($entitlement, $user)))
                || ($action === 'revoke' && ! $assignment)) {
                throw ValidationException::withMessages(['user_ids' => 'Every selected user must belong to this Connector and be eligible for the action. Revocation requires a previous Connector assignment. No users were changed.']);
            }
            $users->push(['user' => $user, 'membership' => $membership, 'assignment' => $assignment]);
        }

        return $users;
    }

    public function manageMembers(ConnectorModuleEntitlement $entitlement, User $actor, string $action, array $userIds): void
    {
        abort_unless($actor->hasRole('admin'), 403);

        $this->changeMembers($entitlement, $actor, $action, $userIds);
    }

    public function manageMember(ConnectorModuleEntitlement $entitlement, User $actor, string $action, int $userId): void
    {
        $this->authorizeEntitlement($entitlement, $actor);
        $this->changeMembers($entitlement, $actor, $action, [$userId]);
    }

    private function changeMembers(ConnectorModuleEntitlement $entitlement, User $actor, string $action, array $userIds): void
    {
        DB::transaction(function () use ($entitlement, $actor, $action, $userIds): void {
            $locked = ConnectorModuleEntitlement::query()->whereKey($entitlement->id)->lockForUpdate()->firstOrFail();
            $locked->load(['connector' => fn ($query) => $query->lockForUpdate(), 'module' => fn ($query) => $query->lockForUpdate()]);
            if (! $actor->hasRole('admin')) {
                $this->authorizeEntitlement($locked, $actor);
            }
            // Re-read eligibility at confirmation; a review page is not an authorization snapshot.
            $selected = $this->selectedMembers($locked, $action, $userIds);
            $before = $selected->mapWithKeys(fn (array $row) => [$row['user']->id => $row['assignment']?->status]);
            foreach ($selected as $row) {
                $user = $row['user'];
                if ($action === 'revoke') {
                    $this->revokeAssignment($locked, $user, $actor, $actor->hasRole('admin') ? 'admin_revocation' : 'manual_revocation');

                    continue;
                }
                $rule = $locked->rules()->firstOrNew(['rule_key' => $this->ruleKey(ConnectorModuleAccessRule::TARGET_MEMBER, $user->id)]);
                $rule->forceFill([
                    'target_type' => ConnectorModuleAccessRule::TARGET_MEMBER,
                    'target_id' => $user->id,
                    'is_active' => true,
                    'created_by' => $rule->created_by ?? $actor->id,
                ])->save();
                if ($row['assignment']?->status === ConnectorModuleSeatAssignment::STATUS_REVOKED) {
                    $row['assignment']->forceFill([
                        'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
                        'revoked_at' => null, 'revoked_by' => null,
                    ])->save();
                }
            }
            if ($action === 'grant') {
                $this->reconcileEntitlement($locked);
            }
            $assignments = $locked->assignments()->whereIn('user_id', $userIds)->get()->keyBy('user_id');
            foreach ($selected as $row) {
                $assignment = $assignments->get($row['user']->id);
                if ($action === 'grant' && ! in_array($assignment?->status, [
                    ConnectorModuleSeatAssignment::STATUS_ACTIVE,
                    ConnectorModuleSeatAssignment::STATUS_PENDING_GUARDIAN,
                    ConnectorModuleSeatAssignment::STATUS_INDEPENDENT,
                ], true)) {
                    throw ValidationException::withMessages(['user_ids' => 'There are not enough available seats for this selection. No users were changed.']);
                }
                if ($before->get($row['user']->id) !== $assignment->status) {
                    if (! $actor->hasRole('admin')) {
                        ActivityLog::log('connector_module_access.'.$action, 'Connector module access '.$action, [
                            'connector_id' => $locked->connector_id, 'module_id' => $locked->module_id,
                            'entitlement_id' => $locked->id, 'user_id' => $row['user']->id,
                            'before_status' => $before->get($row['user']->id), 'status' => $assignment->status,
                        ], $actor->id);

                        continue;
                    }
                    $this->audit->logModelMutation(
                        'connector_module_access.'.$action, $assignment,
                        ['status' => $before->get($row['user']->id)], ['status' => $assignment->status],
                        ['connector_id' => $locked->connector_id, 'module_id' => $locked->module_id, 'user_id' => $row['user']->id,
                            'user_name' => $row['user']->name, 'entitlement_id' => $locked->id],
                        adminUserId: $actor->id,
                    );
                }
            }
        });
    }

    public function managementRoster(ConnectorModuleEntitlement $entitlement): Collection
    {
        $entitlement->loadMissing('connector', 'module.learnerCategories');
        $assignments = $entitlement->assignments()->with('user.learnerProfile', 'user.instructorProfile')->get()->keyBy('user_id');
        $memberships = $entitlement->connector->memberships()->with('user.learnerProfile', 'user.instructorProfile', 'role')->get()->keyBy('user_id');
        $users = $memberships->pluck('user')->merge($assignments->pluck('user'))->unique('id')->values();
        $access = $entitlement->module ? $this->moduleAccess->accessSummaryForUsers($users, $entitlement->module) : collect();

        return $memberships->keys()->merge($assignments->keys())->unique()->map(function ($userId) use ($entitlement, $memberships, $assignments, $access): array {
            $membership = $memberships->get($userId);
            $assignment = $assignments->get($userId);
            $user = $membership?->user ?? $assignment->user;
            $eligible = $membership?->status === 'active' && $this->isEligible($entitlement, $user);
            $canGrant = $eligible && $entitlement->isUsable() && $entitlement->connector->status === 'verified';
            $sources = $access->get($userId, ['independent' => false, 'connector_ids' => []]);
            $hasIndependent = $sources['independent'];
            $hasConnector = in_array((int) $entitlement->connector_id, $sources['connector_ids'], true);
            $hasOtherConnector = count(array_diff($sources['connector_ids'], [(int) $entitlement->connector_id])) > 0;
            $age = $user->calculateAge();
            $ageCategory = $age === null ? null : Module::ageBracketForAge($age);
            $ageLabel = $ageCategory ? Module::learnerCategoryLabelsMap()[$ageCategory] : 'Age unavailable';
            $eligibilityReason = match (true) {
                $membership?->status !== 'active' => 'Membership is not active',
                in_array($user->status, [User::STATUS_SUSPENDED, User::STATUS_INACTIVE, User::STATUS_ARCHIVED], true) => 'Account is not active',
                ! $entitlement->module?->isLearnerVisible() => 'Module is not published',
                ! $eligible => 'Module age group does not match',
                ! $entitlement->isUsable() => 'Organizational license is unavailable',
                $entitlement->connector->status !== 'verified' => 'Connector is not verified',
                $age === null => 'Age is not recorded; existing eligibility rules apply',
                default => 'Learner matches the module age group',
            };
            $canRevoke = $assignment && $assignment->status !== ConnectorModuleSeatAssignment::STATUS_REVOKED;
            $hasAssignment = $assignment && in_array($assignment->status, ['active', 'pending_guardian', 'independent'], true);

            return compact('user', 'membership', 'assignment', 'eligible', 'canGrant', 'hasIndependent', 'hasConnector', 'hasOtherConnector', 'age', 'ageLabel', 'eligibilityReason', 'canRevoke', 'hasAssignment')
                + ['hasAccess' => $hasIndependent || $hasConnector || $hasOtherConnector];
        })->sortBy(fn (array $row) => mb_strtolower($row['user']->name))->values();
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

            $rule = $existing ?? new ConnectorModuleAccessRule;
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

                if (! $matchingRule || ! $this->isEligible($lockedEntitlement, $membership->user)) {
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

                if (! $candidates->contains(fn (array $candidate): bool => (int) $candidate['membership']->user_id === (int) $assignment->user_id)) {
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
                if (! $alreadyConsumesSeat && $seatCount >= (int) $lockedEntitlement->seat_capacity) {
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
                if (! $alreadyConsumesSeat) {
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
            ConnectorModuleEntitlement::query()->whereKey($entitlement->id)->lockForUpdate()->firstOrFail();

            return $this->revokeAssignment($entitlement, $learner, $actor, $reason);
        });
    }

    private function revokeAssignment(ConnectorModuleEntitlement $entitlement, User $learner, User $actor, string $reason): ConnectorModuleSeatAssignment
    {
        $assignment = ConnectorModuleSeatAssignment::query()->firstOrNew([
            'connector_module_entitlement_id' => $entitlement->id,
            'user_id' => $learner->id,
        ]);
        if ($assignment->status === ConnectorModuleSeatAssignment::STATUS_REVOKED) {
            return $assignment;
        }
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
        if (! in_array($targetType, [
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
            if (! $belongs) {
                throw ValidationException::withMessages(['target_id' => 'That role does not belong to this Connector.']);
            }
        }

        if ($targetType === ConnectorModuleAccessRule::TARGET_MEMBER) {
            $belongs = $targetId !== null && $entitlement->connector->memberships()
                ->where('user_id', $targetId)
                ->exists();
            if (! $belongs) {
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
            ->filter(fn (ConnectorMembership $membership): bool => $this->matchesTarget($targetType, $targetId, $membership)
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
        ], true) || ! $entitlement->module?->isLearnerVisible()) {
            return false;
        }

        $age = $user->calculateAge();

        return $age === null || $entitlement->module->isAppropriateForAge($age);
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
            ConnectorModuleAccessRule::TARGET_ROLE => 'role:'.$targetId,
            ConnectorModuleAccessRule::TARGET_MEMBER => 'member:'.$targetId,
            default => throw ValidationException::withMessages(['target_type' => 'Choose a valid access target.']),
        };
    }
}
