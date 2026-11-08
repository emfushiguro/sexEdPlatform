<?php

namespace App\Services\Connectors;

use App\Enums\PaymentStatus;
use App\Models\Connector;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Connectors\ConnectorModulePurchaseNotification;
use App\Services\Modules\ModuleAccessService;
use App\Services\Modules\ModuleCapacityService;
use App\Services\Monetization\ModuleSaleLedgerService;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectorModulePurchaseService
{
    public function __construct(
        private readonly PayMongoPaymentLinkService $payMongo,
        private readonly ConnectorAccessService $access,
        private readonly ModuleCapacityService $capacity,
        private readonly ModuleAccessService $moduleAccess,
        private readonly ModuleSaleLedgerService $ledger,
    ) {}

    public function completePayment(
        Payment $payment,
        ?string $paymentMethod = null,
        ?string $paymongoPaymentId = null,
    ): bool {
        if (! $payment->isConnectorModulePurchase()) {
            return false;
        }

        $notificationStatus = null;

        $completed = DB::transaction(function () use (
            $payment,
            $paymentMethod,
            $paymongoPaymentId,
            &$notificationStatus,
        ): bool {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();
            $purchaseId = (int) data_get($lockedPayment->payment_details, 'connector_module_purchase_id', 0);
            $purchase = ConnectorModulePurchase::query()
                ->whereKey($purchaseId)
                ->lockForUpdate()
                ->firstOrFail();
            if ((int) $purchase->payment_id !== (int) $lockedPayment->id
                || (int) $purchase->purchased_by !== (int) $lockedPayment->user_id
                || (string) $purchase->gross_amount !== (string) $lockedPayment->amount) {
                return false;
            }
            $module = Module::query()
                ->whereKey($purchase->module_id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingEntitlement = $purchase->entitlement()->lockForUpdate()->first();
            if ($purchase->isCompleted() && $existingEntitlement) {
                return true;
            }

            if (! $lockedPayment->isCompleted()) {
                $details = is_array($lockedPayment->payment_details)
                    ? $lockedPayment->payment_details
                    : [];
                $details['payment_scope'] = 'connector_module_purchase';
                if ($paymentMethod !== null) {
                    $details['payment_method'] = $paymentMethod;
                }
                if ($paymongoPaymentId !== null) {
                    $details['paymongo_payment_id'] = $paymongoPaymentId;
                }

                $lockedPayment->forceFill([
                    'status' => PaymentStatus::Completed,
                    'method' => $paymentMethod ?: $lockedPayment->method,
                    'paid_at' => $lockedPayment->paid_at ?? now(),
                    'payment_details' => $details,
                ])->save();
            }

            if (! $module->isLearnerVisible()) {
                $purchase->forceFill([
                    'status' => ConnectorModulePurchase::STATUS_ATTENTION_REQUIRED,
                    'capacity_hold_expires_at' => null,
                    'failure_reason' => 'Module is no longer available for learning.',
                ])->save();
                $notificationStatus = ConnectorModulePurchase::STATUS_ATTENTION_REQUIRED;
                $this->queuePurchaseNotification((int) $purchase->id, $notificationStatus);

                return false;
            }

            if (! $purchase->capacity_hold_expires_at?->isFuture()) {
                try {
                    $this->capacity->assertAvailable(
                        $module,
                        (int) $purchase->seat_capacity,
                        excludeConnectorPurchaseId: $purchase->id,
                    );
                } catch (ValidationException) {
                    $purchase->forceFill([
                        'status' => ConnectorModulePurchase::STATUS_ATTENTION_REQUIRED,
                        'capacity_hold_expires_at' => null,
                        'failure_reason' => 'Paid purchase requires manual capacity recovery.',
                    ])->save();
                    $notificationStatus = ConnectorModulePurchase::STATUS_ATTENTION_REQUIRED;
                    $this->queuePurchaseNotification((int) $purchase->id, $notificationStatus);

                    return false;
                }
            }

            $entitlement = $existingEntitlement ?? ConnectorModuleEntitlement::query()->firstOrCreate(
                ['connector_module_purchase_id' => $purchase->id],
                [
                    'connector_id' => $purchase->connector_id,
                    'module_marketplace_listing_id' => $purchase->module_marketplace_listing_id,
                    'module_id' => $purchase->module_id,
                    'status' => ConnectorModuleEntitlement::STATUS_ACTIVE,
                    'gross_amount' => $purchase->gross_amount,
                    'currency' => $purchase->currency,
                    'seat_capacity' => $purchase->seat_capacity,
                    'activated_at' => now(),
                ],
            );

            $this->ledger->createForCompletedConnectorModulePayment(
                $lockedPayment,
                $purchase,
                $entitlement,
            );

            $purchase->forceFill([
                'status' => ConnectorModulePurchase::STATUS_COMPLETED,
                'capacity_hold_expires_at' => null,
                'provider_paid_at' => $purchase->provider_paid_at ?? $lockedPayment->paid_at ?? now(),
                'purchased_at' => $purchase->purchased_at ?? now(),
            ])->save();
            $notificationStatus = ConnectorModulePurchase::STATUS_COMPLETED;
            $this->queuePurchaseNotification((int) $purchase->id, $notificationStatus);

            return true;
        });

        return $completed;
    }

    private function queuePurchaseNotification(int $purchaseId, string $status): void
    {
        DB::afterCommit(function () use ($purchaseId, $status): void {
            $purchase = ConnectorModulePurchase::query()
                ->with([
                    'connector.memberships.user',
                    'connector.memberships.role.permissions',
                    'purchaser',
                ])
                ->find($purchaseId);

            if (! $purchase) {
                return;
            }

            $recipients = collect([$purchase->purchaser])->filter();
            foreach ($purchase->connector?->memberships ?? [] as $membership) {
                if ($membership->status === 'active'
                    && $membership->role?->permissions?->contains('permission_key', 'connector.manage_modules')) {
                    $recipients->push($membership->user);
                }
            }

            if ($status === ConnectorModulePurchase::STATUS_ATTENTION_REQUIRED) {
                $recipients = $recipients->merge(
                    User::query()
                        ->where('role', 'admin')
                        ->orWhereHas('roles', fn ($query) => $query->where('name', 'admin'))
                        ->get()
                );
            }

            $notification = new ConnectorModulePurchaseNotification($purchase, $status);
            $recipients
                ->filter()
                ->unique('id')
                ->each(fn (User $user) => $user->notify($notification));
        });
    }

    public function verifyAndCompletePendingPayment(Payment $payment): bool
    {
        if (! $payment->isConnectorModulePurchase()) {
            return false;
        }

        if ($payment->isCompleted()) {
            return $this->completePayment($payment);
        }

        $sessionId = (string) data_get($payment->payment_details, 'paymongo_checkout_session_id', '');
        if ($sessionId === '') {
            return false;
        }

        $response = $this->payMongo->retrieveCheckoutSession($sessionId);
        $status = strtolower((string) data_get($response, 'data.attributes.status', ''));
        if (! in_array($status, ['paid', 'completed'], true)) {
            return false;
        }

        $providerPaymentId = $this->payMongo->getActualPaymentIdFromCheckoutSession($sessionId);

        return $this->completePayment($payment, $payment->method, $providerPaymentId);
    }

    public function createCheckout(
        Connector $connector,
        User $actor,
        ModuleMarketplaceListing $listing,
        ?string $paymentMethod,
        array $billing = [],
        ?int $seatCapacity = null,
    ): array {
        $this->access->abortUnlessPermission($actor, $connector, 'connector.manage_modules');

        $state = DB::transaction(function () use ($connector, $actor, $listing, $paymentMethod, $billing, $seatCapacity): array {
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
            if ($purchase?->capacity_hold_expires_at?->isFuture()) {
                if ((int) $purchase->seat_capacity !== ($seatCapacity ?? (int) $lockedListing->seat_capacity)) {
                    throw ValidationException::withMessages(['seat_capacity' => 'A different package checkout is pending. Complete it or wait for its 30-minute hold to expire.']);
                }
                if (! is_string($existingUrl) || $existingUrl === '') {
                    throw ValidationException::withMessages(['seat_capacity' => 'Your checkout is being initialized. Please try again shortly.']);
                }

                return [
                    'status' => 'checkout_created',
                    'checkout_url' => $existingUrl,
                    'payment_id' => $purchase->payment_id,
                    'message' => 'Continuing your pending Connector checkout.',
                ];
            }

            $package = $lockedListing->packageForSeats($seatCapacity);

            if ($purchase?->payment && $purchase->payment->status === PaymentStatus::Pending) {
                $purchase->payment->update(['status' => PaymentStatus::Failed]);
            }

            $this->capacity->assertAvailable(
                $module,
                (int) $package['seat_capacity'],
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
                'amount' => (float) $package['price_amount'],
                'method' => $selectedMethod,
                'status' => PaymentStatus::Pending,
                'transaction_id' => 'CMP-'.strtoupper(uniqid()),
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

            $purchase ??= new ConnectorModulePurchase;
            $purchase->forceFill([
                'connector_id' => $connector->id,
                'module_marketplace_listing_id' => $lockedListing->id,
                'module_id' => $module->id,
                'purchased_by' => $actor->id,
                'payment_id' => $payment->id,
                'status' => ConnectorModulePurchase::STATUS_PENDING,
                'gross_amount' => $package['price_amount'],
                'currency' => strtoupper((string) $lockedListing->currency),
                'seat_capacity' => (int) $package['seat_capacity'],
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
                description: 'Connector Module: '.$state['module_title'],
                remarks: 'Connector module purchase for '.$actor->name,
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
        if ($listing->status !== ModuleMarketplaceListing::STATUS_PUBLISHED || ! $module->isLearnerVisible()) {
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
                    "JSON_SET(COALESCE(payment_details, JSON_OBJECT()), '$.provider_error', ".DB::getPdo()->quote($reason).')'
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
