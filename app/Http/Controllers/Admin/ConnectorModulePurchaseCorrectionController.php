<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CorrectConnectorModulePurchaseRequest;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Services\AdminActivityLogService;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Modules\ModuleCapacityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectorModulePurchaseCorrectionController extends Controller
{
    public function __construct(
        private readonly ConnectorModulePurchaseService $purchases,
        private readonly ConnectorModuleAccessService $access,
        private readonly ModuleCapacityService $capacity,
        private readonly AdminActivityLogService $activity,
    ) {
    }

    public function correct(
        CorrectConnectorModulePurchaseRequest $request,
        ConnectorModulePurchase $purchase,
    ): RedirectResponse {
        $purchase->load([
            'connector',
            'module',
            'payment',
            'entitlement',
        ]);

        if (!$purchase->connector_id) {
            throw ValidationException::withMessages([
                'purchase' => 'Only organization purchases can be corrected here.',
            ]);
        }

        $action = (string) $request->validated('action');
        $reason = (string) $request->validated('reason');
        $before = $this->snapshot($purchase);

        $completed = match ($action) {
            'void' => $this->voidPurchase($purchase, $reason),
            'restore' => $this->restorePurchase($purchase, $reason),
            'retry_finalization' => $this->retryFinalization($purchase),
        };

        $after = $this->snapshot($purchase->fresh(['payment', 'entitlement']));
        $this->activity->logModelMutation(
            action: 'connector_module_purchases.' . $action,
            entity: $purchase,
            before: $before,
            after: $after,
            meta: [
                'reason' => $reason,
                'action' => $action,
                'connector_id' => $purchase->connector_id,
                'module_id' => $purchase->module_id,
                'payment_id' => $purchase->payment_id,
                'ledger_id' => ModuleSaleLedger::query()->where('payment_id', $purchase->payment_id)->value('id'),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.monetization.module-revenue.index')
            ->with($completed === false ? 'warning' : 'success', $completed === false
                ? 'Payment remains pending manual capacity recovery.'
                : 'Connector module purchase correction applied.');
    }

    private function voidPurchase(ConnectorModulePurchase $purchase, string $reason): bool
    {
        DB::transaction(function () use ($purchase, $reason): void {
            $locked = ConnectorModulePurchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();
            $entitlement = $locked->entitlement()->lockForUpdate()->first();

            if ($entitlement) {
                $entitlement->forceFill([
                    'status' => ConnectorModuleEntitlement::STATUS_VOIDED,
                    'status_reason' => $reason,
                    'suspended_at' => now(),
                ])->save();
                $this->access->releaseEntitlement($entitlement, 'admin_correction_void');
            }

            $locked->forceFill([
                'status' => ConnectorModulePurchase::STATUS_CORRECTED,
                'corrected_at' => now(),
                'correction_reason' => $reason,
                'capacity_hold_expires_at' => null,
            ])->save();
        });

        return true;
    }

    private function restorePurchase(ConnectorModulePurchase $purchase, string $reason): bool
    {
        DB::transaction(function () use ($purchase, $reason): void {
            $locked = ConnectorModulePurchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();
            $entitlement = $locked->entitlement()->lockForUpdate()->first();

            if (!$entitlement) {
                throw ValidationException::withMessages([
                    'purchase' => 'This purchase has no entitlement to restore.',
                ]);
            }

            $module = $this->capacity->lock($locked->module);
            if (!$module->isLearnerVisible()) {
                throw ValidationException::withMessages([
                    'purchase' => 'The module is not currently safe to restore.',
                ]);
            }

            $this->capacity->assertAvailable(
                $module,
                (int) $locked->seat_capacity,
                excludeConnectorPurchaseId: $locked->id,
            );

            $entitlement->forceFill([
                'status' => ConnectorModuleEntitlement::STATUS_CORRECTED,
                'status_reason' => $reason,
                'suspended_at' => null,
            ])->save();
            $locked->forceFill([
                'status' => ConnectorModulePurchase::STATUS_CORRECTED,
                'corrected_at' => now(),
                'correction_reason' => $reason,
                'capacity_hold_expires_at' => null,
            ])->save();
            $this->access->reconcileEntitlement($entitlement);
        });

        return true;
    }

    private function retryFinalization(ConnectorModulePurchase $purchase): bool
    {
        $purchase->loadMissing('payment', 'entitlement');
        if ($purchase->entitlement?->status === ConnectorModuleEntitlement::STATUS_VOIDED) {
            throw ValidationException::withMessages([
                'purchase' => 'Restore the voided entitlement before retrying finalization.',
            ]);
        }

        if (!$purchase->payment) {
            throw ValidationException::withMessages([
                'purchase' => 'This purchase has no payment to finalize.',
            ]);
        }

        return $this->purchases->completePayment(
            $purchase->payment,
            $purchase->payment->method,
            data_get($purchase->payment->payment_details, 'paymongo_payment_id'),
        );
    }

    private function snapshot(ConnectorModulePurchase $purchase): array
    {
        return [
            'status' => $purchase->status,
            'corrected_at' => $purchase->corrected_at?->toISOString(),
            'correction_reason' => $purchase->correction_reason,
            'entitlement_status' => $purchase->entitlement?->status,
            'payment_status' => $purchase->payment?->status?->value ?? $purchase->payment?->status,
            'connector_id' => $purchase->connector_id,
            'module_id' => $purchase->module_id,
            'payment_id' => $purchase->payment_id,
            'ledger_id' => ModuleSaleLedger::query()->where('payment_id', $purchase->payment_id)->value('id'),
        ];
    }
}
