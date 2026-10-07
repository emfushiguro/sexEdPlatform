<?php

namespace App\Services\Modules;

use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleAccessGrant;
use App\Models\ModuleEnrollment;
use App\Models\ModulePurchase;
use App\Services\Instructor\InstructorPlanCapabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ModuleCapacityService
{
    public function __construct(
        private readonly InstructorPlanCapabilityService $instructorPlans,
    ) {
    }

    public function lock(Module $module): Module
    {
        return Module::query()
            ->with('creator')
            ->whereKey($module->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function effectiveLimit(Module $module): ?int
    {
        $module->loadMissing('creator');
        $moduleLimit = $module->enrollment_limit !== null ? (int) $module->enrollment_limit : null;

        if ((string) $module->content_owner_type !== 'instructor' || !$module->creator) {
            return $moduleLimit;
        }

        $planLimit = $this->instructorPlans->getLearnerCapForModule(
            $module->creator,
            $module->isPaidAccess() ? 'paid' : 'free',
        );

        return match (true) {
            $moduleLimit === null => $planLimit,
            $planLimit === null => $moduleLimit,
            default => min($moduleLimit, $planLimit),
        };
    }

    /**
     * @return array{independent_learners:int,connector_reserved_seats:int,pending_hold_seats:int,used:int,limit:?int,available:?int}
     */
    public function usage(
        Module $module,
        ?int $excludeConnectorPurchaseId = null,
        ?int $excludeModulePurchaseId = null,
    ): array {
        $connectorUsers = ModuleAccessGrant::query()
            ->where('module_id', $module->id)
            ->where('source_type', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)
            ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
            ->pluck('user_id');

        $independentUsers = ModuleAccessGrant::query()
            ->where('module_id', $module->id)
            ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
            ->where('source_type', '!=', ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT)
            ->pluck('user_id');

        $legacyApprovedUsers = ModuleEnrollment::query()
            ->where('module_id', $module->id)
            ->where('status', 'approved')
            ->pluck('user_id');

        $independentLearners = $independentUsers
            ->merge($legacyApprovedUsers)
            ->unique()
            ->diff($connectorUsers)
            ->count();

        $connectorReservedSeats = (int) ConnectorModuleEntitlement::query()
            ->where('module_id', $module->id)
            ->whereIn('status', [
                ConnectorModuleEntitlement::STATUS_ACTIVE,
                ConnectorModuleEntitlement::STATUS_SUSPENDED,
                ConnectorModuleEntitlement::STATUS_CORRECTED,
            ])
            ->sum('seat_capacity');

        $pendingHoldSeats = (int) ModulePurchase::query()
            ->where('module_id', $module->id)
            ->where('status', ModulePurchase::STATUS_PENDING)
            ->whereNotNull('capacity_hold_expires_at')
            ->where('capacity_hold_expires_at', '>', now())
            ->when($excludeModulePurchaseId, fn (Builder $query) => $query->where('id', '!=', $excludeModulePurchaseId))
            ->count();

        $pendingHoldSeats += (int) ConnectorModulePurchase::query()
            ->where('module_id', $module->id)
            ->where('status', ConnectorModulePurchase::STATUS_PENDING)
            ->whereNotNull('capacity_hold_expires_at')
            ->where('capacity_hold_expires_at', '>', now())
            ->when($excludeConnectorPurchaseId, fn (Builder $query) => $query->where('id', '!=', $excludeConnectorPurchaseId))
            ->sum('seat_capacity');

        $used = $independentLearners + $connectorReservedSeats + $pendingHoldSeats;
        $limit = $this->effectiveLimit($module);

        return [
            'independent_learners' => $independentLearners,
            'connector_reserved_seats' => $connectorReservedSeats,
            'pending_hold_seats' => $pendingHoldSeats,
            'used' => $used,
            'limit' => $limit,
            'available' => $limit === null ? null : max(0, $limit - $used),
        ];
    }

    public function assertAvailable(
        Module $module,
        int $requestedSeats,
        ?int $excludeConnectorPurchaseId = null,
        ?int $excludeModulePurchaseId = null,
    ): void {
        if ($requestedSeats < 1) {
            throw ValidationException::withMessages([
                'capacity' => 'At least one seat is required.',
            ]);
        }

        $usage = $this->usage($module, $excludeConnectorPurchaseId, $excludeModulePurchaseId);
        if ($usage['limit'] !== null && $usage['used'] + $requestedSeats > $usage['limit']) {
            throw ValidationException::withMessages([
                'capacity' => 'Module capacity is not available.',
            ]);
        }
    }
}
