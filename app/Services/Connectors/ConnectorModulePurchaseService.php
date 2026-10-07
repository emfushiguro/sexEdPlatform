<?php

namespace App\Services\Connectors;

use App\Enums\PaymentStatus;
use App\Models\Connector;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\Payment;
use App\Models\User;
use App\Services\Modules\ModuleCapacityService;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectorModulePurchaseService
{
    public function __construct(
        private readonly PayMongoPaymentLinkService $payMongo,
        private readonly ConnectorAccessService $access,
        private readonly ModuleCapacityService $capacity,
    ) {
    }

    public function createCheckout(
        Connector $connector,
        User $actor,
        ModuleMarketplaceListing $listing,
        ?string $paymentMethod,
        array $billing = [],
    ): array {
        $this->access->abortUnlessPermission($actor, $connector, 'connector.manage_modules');

        $state = DB::transaction(function () use ($connector, $actor, $listing, $paymentMethod, $billing): array {
            $lockedListing = ModuleMarketplaceListing::query()
                ->with('module.creator')
                ->whereKey($listing->id)
                ->lockForUpdate()
                ->firstOrFail();
            $module = $this->capacity->lock($lockedListing->module);

            $this->validateListing($connector, $lockedListing, $module);

            $completed = ConnectorModulePurchase::query()
                ->where('connector_id', $connector->id)
                ->where('module_id', $module->id)
                ->where('status', ConnectorModulePurchase::STATUS_COMPLETED)
                ->exists();
            if ($completed) {
                return ['status' => 'already_purchased', 'message' => 'This Connector already owns this module package.'];
            }

            $purchase = ConnectorModulePurchase::query()
                ->where('connector_id', $connector->id)
                ->where('module_id', $module->id)
                ->where('status', ConnectorModulePurchase::STATUS_PENDING)
                ->with('payment')
                ->lockForUpdate()
                ->first();
            $existingUrl = data_get($purchase?->payment?->payment_details, 'checkout_url');
            if ($purchase?->capacity_hold_expires_at?->isFuture() && is_string($existingUrl) && $existingUrl !== '') {
                return [
                    'status' => 'checkout_created',
                    'checkout_url' => $existingUrl,
                    'payment_id' => $purchase->payment_id,
                    'message' => 'Continuing your pending Connector checkout.',
                ];
            }

            if ($purchase?->payment && $purchase->payment->status === PaymentStatus::Pending) {
                $purchase->payment->update(['status' => PaymentStatus::Failed]);
            }

            $this->capacity->assertAvailable(
                $module,
                (int) $lockedListing->seat_capacity,
                excludeConnectorPurchaseId: $purchase?->id,
            );

            $selectedMethod = in_array($paymentMethod, ['gcash', 'paymaya', 'grab_pay', 'card'], true)
                ? $paymentMethod
                : 'gcash';
            $billing = [
                'name' => (string) ($billing['name'] ?? $actor->name),
                'email' => (string) ($billing['email'] ?? $actor->email ?? ''),
                'phone' => (string) ($billing['phone'] ?? ''),
            ];
            $sellerType = $module->content_owner_type === 'admin' ? 'platform' : 'instructor';

            $payment = Payment::query()->create([
                'user_id' => $actor->id,
                'subscription_id' => null,
                'amount' => (float) $lockedListing->price_amount,
                'method' => $selectedMethod,
                'status' => PaymentStatus::Pending,
                'transaction_id' => 'CMP-' . strtoupper(uniqid()),
                'payment_details' => [
                    'payment_scope' => 'connector_module_purchase',
                    'payment_method' => $selectedMethod,
                    'connector_id' => $connector->id,
                    'connector_module_purchase_id' => $purchase?->id,
                    'marketplace_listing_id' => $lockedListing->id,
                    'module_id' => $module->id,
                    'user_id' => $actor->id,
                    'billing' => $billing,
                ],
            ]);

            $purchase ??= new ConnectorModulePurchase();
            $purchase->forceFill([
                'connector_id' => $connector->id,
                'module_marketplace_listing_id' => $lockedListing->id,
                'module_id' => $module->id,
                'purchased_by' => $actor->id,
                'payment_id' => $payment->id,
                'status' => ConnectorModulePurchase::STATUS_PENDING,
                'gross_amount' => $lockedListing->price_amount,
                'currency' => strtoupper((string) $lockedListing->currency),
                'seat_capacity' => (int) $lockedListing->seat_capacity,
                'seller_type_snapshot' => $sellerType,
                'seller_user_id_snapshot' => $sellerType === 'instructor' ? $module->created_by : null,
                'module_title_snapshot' => (string) $module->title,
                'terms_version' => (string) config('billing.module_marketplace.terms_version', ConnectorModulePurchase::TERMS_VERSION),
                'terms_accepted_at' => now(),
                'capacity_hold_expires_at' => now()->addMinutes(
                    (int) config('billing.module_marketplace.capacity_hold_minutes', 30)
                ),
                'metadata' => [
                    'billing' => $billing,
                    'payment_method' => $selectedMethod,
                ],
            ])->save();

            $payment->update([
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'connector_module_purchase_id' => $purchase->id,
                ]),
            ]);

            return [
                'status' => 'provider_required',
                'payment_id' => $payment->id,
                'purchase_id' => $purchase->id,
                'amount' => (float) $purchase->gross_amount,
                'module_title' => (string) $purchase->module_title_snapshot,
                'payment_method' => $selectedMethod,
                'billing' => $billing,
                'metadata' => [
                    'payment_scope' => 'connector_module_purchase',
                    'connector_id' => $connector->id,
                    'connector_module_purchase_id' => $purchase->id,
                    'marketplace_listing_id' => $lockedListing->id,
                    'module_id' => $module->id,
                    'user_id' => $actor->id,
                    'payment_id' => $payment->id,
                ],
            ];
        });

        if (($state['status'] ?? null) !== 'provider_required') {
            return $state;
        }

        try {
            $response = $this->payMongo->createCheckoutSession(
                amount: (float) $state['amount'],
                description: 'Connector Module: ' . $state['module_title'],
                remarks: 'Connector module purchase for ' . $actor->name,
                metadata: $state['metadata'],
                successUrl: route('payment.pending', ['payment' => $state['payment_id']]),
                cancelUrl: route('payment.cancel', [
                    'scope' => 'connector_module_purchase',
                    'payment_id' => $state['payment_id'],
                ]),
                preferredPaymentMethod: $state['payment_method'],
                lineItemName: $state['module_title'],
            );
            $checkoutUrl = (string) data_get($response, 'data.attributes.checkout_url', '');
            $checkoutSessionId = data_get($response, 'data.id');
            if ($checkoutUrl === '') {
                throw new \RuntimeException('PayMongo did not return checkout URL.');
            }
        } catch (\Throwable $exception) {
            $this->markProviderFailure((int) $state['payment_id'], (int) $state['purchase_id'], $exception->getMessage());
            throw $exception;
        }

        DB::transaction(function () use ($state, $checkoutUrl, $checkoutSessionId): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($state['payment_id']);
            $payment->update([
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'paymongo_checkout_session_id' => $checkoutSessionId,
                    'checkout_url' => $checkoutUrl,
                ]),
            ]);
            ConnectorModulePurchase::query()->whereKey($state['purchase_id'])->update([
                'metadata' => array_merge(
                    ConnectorModulePurchase::query()->findOrFail($state['purchase_id'])->metadata ?? [],
                    ['paymongo_checkout_session_id' => $checkoutSessionId],
                ),
            ]);
        });

        return [
            'status' => 'checkout_created',
            'checkout_url' => $checkoutUrl,
            'payment_id' => $state['payment_id'],
            'message' => 'Redirecting to secure Connector checkout.',
        ];
    }

    private function validateListing(
        Connector $connector,
        ModuleMarketplaceListing $listing,
        Module $module,
    ): void {
        if ($listing->status !== ModuleMarketplaceListing::STATUS_PUBLISHED || !$module->isLearnerVisible()) {
            throw ValidationException::withMessages(['listing' => 'This marketplace listing is no longer available.']);
        }

        $expectedSeller = $module->content_owner_type === 'admin' ? 'platform' : 'instructor';
        if ($listing->seller_type !== $expectedSeller
            || ($expectedSeller === 'instructor' && (int) $listing->seller_user_id !== (int) $module->created_by)) {
            throw ValidationException::withMessages(['listing' => 'The listing seller is not valid for this module.']);
        }
    }

    private function markProviderFailure(int $paymentId, int $purchaseId, string $reason): void
    {
        DB::transaction(function () use ($paymentId, $purchaseId, $reason): void {
            Payment::query()->whereKey($paymentId)->update([
                'status' => PaymentStatus::Failed,
                'payment_details' => DB::raw(
                    "JSON_SET(COALESCE(payment_details, JSON_OBJECT()), '$.provider_error', " . DB::getPdo()->quote($reason) . ')'
                ),
            ]);
            ConnectorModulePurchase::query()->whereKey($purchaseId)->update([
                'status' => ConnectorModulePurchase::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => $reason,
                'capacity_hold_expires_at' => null,
            ]);
        });
    }
}
