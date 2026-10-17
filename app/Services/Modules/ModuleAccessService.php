<?php

namespace App\Services\Modules;

use App\Enums\EnrollmentStatus;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\Module;
use App\Models\ModuleAccessGrant;
use App\Models\ModuleEnrollment;
use App\Models\ModulePurchase;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ModuleAccessService
{
    public function grantDirectEnrollment(ModuleEnrollment $enrollment): ModuleAccessGrant
    {
        $enrollment->loadMissing('module');
        $status = match ($enrollment->status) {
            EnrollmentStatus::Approved => ModuleAccessGrant::STATUS_ACTIVE,
            EnrollmentStatus::Pending, EnrollmentStatus::PendingParentApproval => ModuleAccessGrant::STATUS_PENDING,
            default => ModuleAccessGrant::STATUS_INACTIVE,
        };

        $grant = $this->storeGrant(
            userId: (int) $enrollment->user_id,
            moduleId: (int) $enrollment->module_id,
            sourceType: ModuleAccessGrant::SOURCE_DIRECT_ENROLLMENT,
            sourceId: (int) $enrollment->id,
            status: $status,
        );

        $manualApprovalRequired = $enrollment->module?->enrollment_mode === 'manual'
            && $enrollment->status === EnrollmentStatus::Pending;

        return $manualApprovalRequired ? $grant->fresh() : $this->syncGrant($grant);
    }

    public function grantIndividualPurchase(ModulePurchase $purchase): ModuleAccessGrant
    {
        $purchase->loadMissing('module');
        $enrollment = ModuleEnrollment::query()
            ->where('user_id', $purchase->user_id)
            ->where('module_id', $purchase->module_id)
            ->first();
        $manualApprovalRequired = $purchase->module?->enrollment_mode === 'manual'
            && (! $enrollment || in_array($enrollment->status, [
                EnrollmentStatus::Pending,
                EnrollmentStatus::PendingParentApproval,
            ], true));

        if (! $enrollment && $manualApprovalRequired) {
            $enrollment = ModuleEnrollment::query()->create([
                'user_id' => $purchase->user_id,
                'module_id' => $purchase->module_id,
                'status' => EnrollmentStatus::Pending,
            ]);
        }

        $status = $purchase->isCompleted()
            ? ModuleAccessGrant::STATUS_ACTIVE
            : ($purchase->status === ModulePurchase::STATUS_PENDING
                ? ModuleAccessGrant::STATUS_PENDING
                : ModuleAccessGrant::STATUS_INACTIVE);

        $grant = $this->storeGrant(
            userId: (int) $purchase->user_id,
            moduleId: (int) $purchase->module_id,
            sourceType: ModuleAccessGrant::SOURCE_INDIVIDUAL_PURCHASE,
            sourceId: (int) $purchase->id,
            status: $status,
        );

        return $manualApprovalRequired ? $grant->fresh() : $this->syncGrant($grant);
    }

    public function grantConnectorAssignment(
        ConnectorModuleSeatAssignment $assignment,
        bool $guardianPending = false,
    ): ModuleAccessGrant {
        $assignment->loadMissing('entitlement');
        $entitlement = $assignment->entitlement;
        $isPending = $guardianPending
            || $assignment->status === ConnectorModuleSeatAssignment::STATUS_PENDING_GUARDIAN;
        $isUsable = $entitlement?->isUsable() === true;
        $isActive = in_array($assignment->status, [
            ConnectorModuleSeatAssignment::STATUS_ACTIVE,
            ConnectorModuleSeatAssignment::STATUS_INDEPENDENT,
        ], true);

        $status = ! $isUsable || (! $isPending && ! $isActive)
            ? ModuleAccessGrant::STATUS_INACTIVE
            : ($isPending ? ModuleAccessGrant::STATUS_PENDING : ModuleAccessGrant::STATUS_ACTIVE);

        $grant = $this->storeGrant(
            userId: (int) $assignment->user_id,
            moduleId: (int) ($entitlement?->module_id ?? 0),
            sourceType: ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            sourceId: (int) $assignment->id,
            connectorId: $entitlement?->connector_id,
            status: $status,
        );

        $grant = $this->syncGrant($grant);

        if (! $isPending && $status === ModuleAccessGrant::STATUS_ACTIVE) {
            $enrollment = ModuleEnrollment::query()
                ->where('user_id', $grant->user_id)
                ->where('module_id', $grant->module_id)
                ->first();

            if ($enrollment?->status === EnrollmentStatus::PendingParentApproval) {
                $enrollment->forceFill([
                    'status' => EnrollmentStatus::Approved,
                    'enrolled_at' => $enrollment->enrolled_at ?? now(),
                    'inactive_reason' => null,
                    'inactivated_at' => null,
                ])->save();
            }
        }

        return $grant;
    }

    public function activateSource(string $sourceType, int $sourceId): ModuleAccessGrant
    {
        return match ($sourceType) {
            ModuleAccessGrant::SOURCE_DIRECT_ENROLLMENT => $this->grantDirectEnrollment(
                ModuleEnrollment::query()->findOrFail($sourceId),
            ),
            ModuleAccessGrant::SOURCE_INDIVIDUAL_PURCHASE => $this->grantIndividualPurchase(
                ModulePurchase::query()->findOrFail($sourceId),
            ),
            ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT => $this->grantConnectorAssignment(
                ConnectorModuleSeatAssignment::query()->findOrFail($sourceId),
            ),
            default => throw new \InvalidArgumentException('Unknown module access source.'),
        };
    }

    public function deactivateSource(string $sourceType, int $sourceId, string $reason): void
    {
        DB::transaction(function () use ($sourceType, $sourceId, $reason): void {
            $grant = ModuleAccessGrant::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if (! $grant) {
                return;
            }

            $grant->forceFill([
                'status' => ModuleAccessGrant::STATUS_INACTIVE,
                'ended_at' => now(),
                'reason' => $reason,
            ])->save();

            $this->syncEnrollment(
                User::query()->findOrFail($grant->user_id),
                Module::withTrashed()->findOrFail($grant->module_id),
            );
        });
    }

    public function syncEnrollment(User $user, Module $module): ModuleEnrollment
    {
        return DB::transaction(function () use ($user, $module): ModuleEnrollment {
            $enrollment = ModuleEnrollment::query()
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->lockForUpdate()
                ->first();

            $hasActiveGrant = ModuleAccessGrant::query()
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
                ->exists();

            if (! $enrollment) {
                $pendingGrant = ModuleAccessGrant::query()
                    ->where('user_id', $user->id)
                    ->where('module_id', $module->id)
                    ->where('status', ModuleAccessGrant::STATUS_PENDING)
                    ->exists();

                return ModuleEnrollment::query()->create([
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'status' => $hasActiveGrant
                        ? EnrollmentStatus::Approved
                        : ($pendingGrant ? EnrollmentStatus::PendingParentApproval : EnrollmentStatus::Inactive),
                    'enrolled_at' => $hasActiveGrant ? now() : null,
                    'inactive_reason' => $hasActiveGrant ? null : 'no_active_access_source',
                    'inactivated_at' => $hasActiveGrant ? null : now(),
                ]);
            }

            if ($hasActiveGrant && ! in_array($enrollment->status, [
                EnrollmentStatus::PendingParentApproval,
                EnrollmentStatus::Rejected,
            ], true)) {
                $enrollment->forceFill([
                    'status' => EnrollmentStatus::Approved,
                    'enrolled_at' => $enrollment->enrolled_at ?? now(),
                    'inactive_reason' => null,
                    'inactivated_at' => null,
                ])->save();
            } elseif (! $hasActiveGrant && $enrollment->status === EnrollmentStatus::Approved) {
                $enrollment->forceFill([
                    'status' => EnrollmentStatus::Inactive,
                    'inactive_reason' => 'no_active_access_source',
                    'inactivated_at' => now(),
                ])->save();
            }

            return $enrollment->fresh();
        });
    }

    public function hasActiveAccess(User $user, Module $module): bool
    {
        if (! $this->userCanAccessModule($user, $module)) {
            return false;
        }

        if (ModuleAccessGrant::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
            ->where('source_type', '!=', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)
            ->exists()) {
            return true;
        }

        if (ModuleAccessGrant::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->exists()) {
            return ModuleAccessGrant::query()
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('source_type', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)
                ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
                ->get()
                ->contains(fn (ModuleAccessGrant $grant): bool => $this->connectorGrantIsValid($grant, $user, $module));
        }

        // Keep legacy test/imported enrollments usable until their grant backfill runs.
        return ModuleEnrollment::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', EnrollmentStatus::Approved)
            ->exists();
    }

    public function accessSummaryForUsers(\Illuminate\Support\Collection $users, Module $module): \Illuminate\Support\Collection
    {
        $grants = ModuleAccessGrant::query()->where('module_id', $module->id)
            ->whereIn('user_id', $users->pluck('id'))->get();
        $assignments = ConnectorModuleSeatAssignment::query()->with('entitlement.connector', 'membership')
            ->whereIn('id', $grants->where('source_type', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)->pluck('source_id'))
            ->get()->keyBy('id');
        $legacy = ModuleEnrollment::query()->where('module_id', $module->id)->whereIn('user_id', $users->pluck('id'))
            ->where('status', EnrollmentStatus::Approved)->pluck('user_id')->flip();
        $byUser = $grants->groupBy('user_id');

        return $users->mapWithKeys(function (User $user) use ($module, $byUser, $assignments, $legacy): array {
            $userGrants = $byUser->get($user->id, collect());
            $active = $userGrants->where('status', ModuleAccessGrant::STATUS_ACTIVE);
            $allowed = $this->userCanAccessModule($user, $module);
            $independent = $allowed && ($active->where('source_type', '!=', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)->isNotEmpty()
                || ($userGrants->isEmpty() && $legacy->has($user->id)));
            $connectorIds = $allowed ? $active->where('source_type', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)
                ->filter(fn (ModuleAccessGrant $grant): bool => $this->connectorAssignmentIsValid($assignments->get($grant->source_id), $user, $module))
                ->map(fn (ModuleAccessGrant $grant) => (int) $assignments->get($grant->source_id)->entitlement->connector_id)->unique()->values()->all() : [];

            return [$user->id => ['independent' => $independent, 'connector_ids' => $connectorIds]];
        });
    }

    private function userCanAccessModule(User $user, Module $module): bool
    {
        return ! in_array($user->status, [User::STATUS_SUSPENDED, User::STATUS_INACTIVE, User::STATUS_ARCHIVED], true)
            && $module->isLearnerVisible();
    }

    public function assertCanLearn(User $user, Module $module): void
    {
        if (! $this->hasActiveAccess($user, $module)) {
            throw new AuthorizationException('You do not have active access to this module.');
        }

        $enrollment = ModuleEnrollment::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->first();

        if (! $enrollment || $enrollment->status !== EnrollmentStatus::Approved) {
            throw new AuthorizationException('Your enrollment does not permit learning this module.');
        }
    }

    public function activeConnectorSources(User $user, Module $module): Collection
    {
        return ModuleAccessGrant::query()
            ->with('connector')
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('source_type', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)
            ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
            ->get()
            ->filter(fn (ModuleAccessGrant $grant): bool => $this->connectorGrantIsValid($grant, $user, $module))
            ->values();
    }

    public function hasHistoricalAccess(User $user, Module $module): bool
    {
        if (ModuleAccessGrant::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->whereIn('status', [
                ModuleAccessGrant::STATUS_ACTIVE,
                ModuleAccessGrant::STATUS_INACTIVE,
            ])
            ->exists()) {
            return true;
        }

        return ModuleEnrollment::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->whereIn('status', [EnrollmentStatus::Approved, EnrollmentStatus::Inactive])
            ->exists();
    }

    private function storeGrant(
        int $userId,
        int $moduleId,
        string $sourceType,
        int $sourceId,
        string $status,
        ?int $connectorId = null,
    ): ModuleAccessGrant {
        $grant = ModuleAccessGrant::query()->firstOrNew([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
        $wasActive = $grant->exists && $grant->status === ModuleAccessGrant::STATUS_ACTIVE;

        $grant->forceFill([
            'user_id' => $userId,
            'module_id' => $moduleId,
            'connector_id' => $connectorId,
            'status' => $status,
            'ended_at' => $status === ModuleAccessGrant::STATUS_INACTIVE ? now() : null,
            'reason' => $status === ModuleAccessGrant::STATUS_INACTIVE ? $grant->reason : null,
        ]);

        if (! $grant->exists || (! $wasActive && $status === ModuleAccessGrant::STATUS_ACTIVE)) {
            $grant->granted_at = now();
        }

        $grant->save();

        return $grant;
    }

    private function syncGrant(ModuleAccessGrant $grant): ModuleAccessGrant
    {
        $this->syncEnrollment(
            User::query()->findOrFail($grant->user_id),
            Module::query()->findOrFail($grant->module_id),
        );

        return $grant->fresh();
    }

    private function connectorGrantIsValid(
        ModuleAccessGrant $grant,
        User $user,
        Module $module,
    ): bool {
        $assignment = ConnectorModuleSeatAssignment::query()
            ->with(['entitlement.connector', 'membership'])
            ->whereKey($grant->source_id)
            ->first();

        return $this->connectorAssignmentIsValid($assignment, $user, $module);
    }

    private function connectorAssignmentIsValid(?ConnectorModuleSeatAssignment $assignment, User $user, Module $module): bool
    {
        $age = $user->calculateAge();

        return $assignment !== null
            && ($age === null || $module->isAppropriateForAge($age))
            && (int) $assignment->user_id === (int) $user->id
            && $assignment->released_at === null
            && in_array($assignment->status, [
                ConnectorModuleSeatAssignment::STATUS_ACTIVE,
                ConnectorModuleSeatAssignment::STATUS_INDEPENDENT,
            ], true)
            && $assignment->entitlement?->isUsable() === true
            && $assignment->entitlement?->connector?->status === 'verified'
            && $assignment->membership?->status === 'active'
            && (int) $assignment->entitlement?->module_id === (int) $module->id;
    }
}
