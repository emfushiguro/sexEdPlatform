<?php

namespace App\Services\Checkout;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ModulePurchaseService;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\DB;

class LearnerCheckoutService
{
    public function __construct(
        private readonly ModulePurchaseService $modulePurchaseService,
        private readonly PayMongoPaymentLinkService $payMongoPaymentLinkService,
    ) {
    }

    public function buildModuleContext(User $user, Module $module): array
    {
        $module->loadMissing('creator');

        return [
            'scope' => 'module_purchase',
            'user_id' => $user->id,
            'module_id' => $module->id,
            'item_name' => (string) $module->title,
            'description' => (string) ($module->description ?? ''),
            'instructor_name' => (string) ($module->creator?->name ?? 'Instructor'),
            'amount' => (float) ($module->price_amount ?? 0),
            'currency' => strtoupper((string) ($module->price_currency ?? 'PHP')),
        ];
    }

    public function buildSubscriptionContext(User $user, Subscription $subscription): array
    {
        $subscription->loadMissing('plan');

        $planName = $subscription->relationLoaded('plan')
            ? ($subscription->getRelation('plan')?->name ?? null)
            : null;

        $planName = $planName
            ?? ucfirst((string) $subscription->plan) . ' Subscription';

        return [
            'scope' => 'subscription',
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'item_name' => (string) $planName,
            'description' => 'Subscription checkout',
            'amount' => (float) $subscription->getAmount(),
            'currency' => 'PHP',
        ];
    }

    /**
     * @param Module|Subscription $subject
     * @return array{status:string, scope:string, checkout_url?:string, payment_id?:int, message:string}
     */
    public function createCheckout(
        string $scope,
        User $user,
        Module|Subscription $subject,
        string $paymentMethod,
        array $billing = []
    ): array {
        return match ($scope) {
            'module_purchase' => $this->createModuleCheckout($user, $subject, $paymentMethod, $billing),
            'subscription' => $this->createSubscriptionCheckout($user, $subject, $paymentMethod, $billing),
            default => throw new \InvalidArgumentException('Unsupported checkout scope.'),
        };
    }

    private function createModuleCheckout(
        User $user,
        Module|Subscription $subject,
        string $paymentMethod,
        array $billing = []
    ): array {
        if (!$subject instanceof Module) {
            throw new \InvalidArgumentException('Module checkout requires a module subject.');
        }

        $checkout = $this->modulePurchaseService->createCheckout($user, $subject, $paymentMethod, $billing);

        return array_merge($checkout, ['scope' => 'module_purchase']);
    }

    private function createSubscriptionCheckout(
        User $user,
        Module|Subscription $subject,
        string $paymentMethod,
        array $billing = []
    ): array {
        if (!$subject instanceof Subscription) {
            throw new \InvalidArgumentException('Subscription checkout requires a subscription subject.');
        }

        if ((int) $subject->user_id !== (int) $user->id) {
            throw new \InvalidArgumentException('Subscription does not belong to the current user.');
        }

        return DB::transaction(function () use ($user, $subject, $paymentMethod, $billing) {
            $billingName = (string) ($billing['name'] ?? $user->name);
            $billingEmail = (string) ($billing['email'] ?? $user->email ?? '');
            $billingPhone = (string) ($billing['phone'] ?? '');

            $payment = Payment::query()
                ->where('subscription_id', $subject->id)
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->first();

            if (!$payment) {
                $payment = $subject->payments()->create([
                    'user_id' => $user->id,
                    'amount' => (float) $subject->getAmount(),
                    'method' => $paymentMethod,
                    'status' => PaymentStatus::Pending,
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_details' => [
                        'payment_scope' => 'subscription',
                        'payment_method' => $paymentMethod,
                        'billing' => [
                            'name' => $billingName,
                            'email' => $billingEmail,
                            'phone' => $billingPhone,
                        ],
                    ],
                ]);
            } else {
                $payment->update([
                    'method' => $paymentMethod,
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'payment_scope' => 'subscription',
                        'payment_method' => $paymentMethod,
                        'billing' => [
                            'name' => $billingName,
                            'email' => $billingEmail,
                            'phone' => $billingPhone,
                        ],
                    ]),
                ]);
            }

            $subject->loadMissing('plan');
            $planName = $subject->relationLoaded('plan')
                ? ($subject->getRelation('plan')?->name ?? null)
                : null;

            $planName = $planName
                ?? ucfirst((string) $subject->plan) . ' Subscription';

            $response = $this->payMongoPaymentLinkService->createCheckoutSession(
                amount: (float) $subject->getAmount(),
                description: $planName,
                remarks: 'Subscription checkout for ' . $user->name,
                metadata: [
                    'payment_scope' => 'subscription',
                    'user_id' => $user->id,
                    'subscription_id' => $subject->id,
                    'payment_id' => $payment->id,
                    'billing_name' => $billingName,
                    'billing_email' => $billingEmail,
                ],
                successUrl: route('payment.paymongo.success', ['subscription' => $subject->id]),
                cancelUrl: route('payment.paymongo.failed', ['subscription' => $subject->id]),
                preferredPaymentMethod: $paymentMethod,
                lineItemName: $planName,
            );

            $checkoutUrl = (string) data_get($response, 'data.attributes.checkout_url', '');
            $checkoutSessionId = data_get($response, 'data.id');

            if ($checkoutUrl === '') {
                throw new \RuntimeException('PayMongo did not return checkout URL.');
            }

            $payment->update([
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'payment_scope' => 'subscription',
                    'paymongo_checkout_session_id' => $checkoutSessionId,
                    'checkout_url' => $checkoutUrl,
                    'payment_method' => $paymentMethod,
                    'billing' => [
                        'name' => $billingName,
                        'email' => $billingEmail,
                        'phone' => $billingPhone,
                    ],
                ]),
            ]);

            return [
                'status' => 'checkout_created',
                'scope' => 'subscription',
                'checkout_url' => $checkoutUrl,
                'payment_id' => (int) $payment->id,
                'message' => 'Redirecting to secure checkout.',
            ];
        });
    }
}
