<?php

namespace Tests\Feature\Parent\SubscriptionGifting;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendPaymentReceiptEmail;
use App\Jobs\SendSubscriptionWelcomeEmail;
use App\Mail\PaymentReceiptMail;
use App\Models\ParentChildAccount;
use App\Models\Payment;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\Learner\SubscriptionResultNotification;
use App\Services\GuardianSubscriptionGiftService;
use App\Services\SubscriptionDunningService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuardianSubscriptionGiftTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Bus::fake([GenerateInvoiceJob::class, SendPaymentReceiptEmail::class, SendSubscriptionWelcomeEmail::class]);
        Notification::fake();
        config(['paymongo.secret_key' => 'sk_test_gift', 'paymongo.webhook_secret' => 'gift_webhook_secret']);
    }

    public function test_verified_guardian_prepares_dependent_subscription_and_owns_payment(): void
    {
        [$guardian, $dependent, $plan] = $this->family();

        $response = $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ]);

        $response->assertStatus(302);
        $payment = Payment::query()->sole();
        $response->assertRedirect(route('payment.checkout.summary', [
            'subscription' => $payment->subscription_id, 'gift_payment' => $payment->id,
        ]));
        $this->assertSame($guardian->id, $payment->user_id);
        $this->assertSame($dependent->id, $payment->subscription->user_id);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertFalse($dependent->fresh()->isPremium());
        $this->assertFalse($guardian->fresh()->isPremium());
        $this->assertSame(12900, data_get($payment->payment_details, 'gift.amount_minor'));
        $this->assertSame(1, data_get($payment->payment_details, 'gift.duration_count'));
        $this->assertFalse($payment->subscription->auto_renew);
    }

    public function test_purchase_requires_an_approved_relationship_even_with_progress_permission(): void
    {
        [$guardian, $dependent, $plan, $link] = $this->family();
        $link->update(['verification_status' => 'pending', 'verification_document_path' => 'verification/pending.pdf']);
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertForbidden();
        $this->assertSame(0, Payment::count());
    }

    public function test_purchasing_does_not_require_monitoring_permissions(): void
    {
        [$guardian, $dependent, $plan, $link] = $this->family();
        $link->update(['can_view_progress' => false, 'can_view_quiz_answers' => false]);
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertRedirect();
        $this->assertSame(1, Payment::count());
        $this->assertFalse($link->fresh()->can_view_progress);
    }

    public function test_gifting_respects_current_verified_and_revoked_relationship_states(): void
    {
        [$guardian, $dependent, $plan, $link] = $this->family();
        $payload = ['plan_id' => $plan->id, 'request_key' => (string) Str::uuid()];
        $link->update(['relationship_verified_status' => ParentChildAccount::VERIFICATION_REVOKED]);
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), $payload)->assertForbidden();
        $link->update([
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_status' => ParentChildAccount::STATUS_INACTIVE,
        ]);
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), $payload)->assertForbidden();
        $this->assertSame(0, Payment::count());

        // An invited, verified dependent has no legacy PSA-document approval requirement.
        $link->update(['relationship_status' => ParentChildAccount::STATUS_ACTIVE, 'verification_status' => 'pending']);
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), $payload)->assertRedirect();
        $this->assertSame($dependent->id, Payment::query()->sole()->subscription->user_id);
    }

    public function test_unverified_guardian_and_unrelated_or_self_purchase_are_forbidden(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $guardian->update(['parent_verification_status' => 'pending']);
        $payload = ['plan_id' => $plan->id, 'request_key' => (string) Str::uuid()];
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), $payload)->assertForbidden();
        $guardian->update(['parent_verification_status' => 'approved']);
        $this->actingAs($guardian)->post($this->purchaseUrl($guardian), $payload)->assertForbidden();
        $stranger = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $this->actingAs($guardian)->post($this->purchaseUrl($stranger), $payload)->assertForbidden();
        $this->actingAs($dependent)->post($this->purchaseUrl($dependent), $payload)->assertForbidden();
        $this->assertSame(0, Payment::count());
    }

    public function test_repeated_purchase_request_reuses_one_payment(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payload = ['plan_id' => $plan->id, 'request_key' => (string) Str::uuid()];
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), $payload)->assertRedirect();
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), $payload)->assertRedirect();
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Subscription::count());
    }

    public function test_other_audience_and_invalid_request_key_cannot_create_payment(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $plan->update(['plan_audience' => 'instructor']);
        $this->actingAs($guardian)->postJson($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertUnprocessable();
        $this->actingAs($guardian)->postJson($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => 'invalid',
        ])->assertUnprocessable();
        $this->assertSame(0, Payment::count());
    }

    public function test_existing_checkout_is_available_to_payer_but_not_dependent_or_other_guardian(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $url = $this->checkoutUrl($payment);
        $this->actingAs($guardian)->get($url)->assertOk()->assertSee('Gift Dependent');
        $this->actingAs($dependent)->get($url)->assertForbidden();
        $this->actingAs($dependent)->get(route('payment.checkout.summary', $payment->subscription))->assertForbidden();
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get($url)->assertForbidden();
        $this->actingAs($dependent)->get(route('payment.receipt', $payment))->assertForbidden();
        $this->actingAs($guardian)->get(route('payment.receipt', $payment))->assertNotFound();
        $this->actingAs($guardian)->get(route('payment.pending', $payment))->assertOk();
    }

    public function test_gift_checkout_reuses_existing_gateway_and_session_with_frozen_price(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $plan->update(['price' => 999]);
        $plan->planPrices()->update(['amount_minor' => 99900]);
        Http::fake(['api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => ['id' => 'cs_gift_checkout', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/gift-test']],
        ])]);

        $payload = ['accept_terms' => true, 'payment_method' => 'gcash', 'billing_email' => 'payer-private@example.test'];
        $this->actingAs($guardian)->post($this->checkoutUrl($payment), $payload)
            ->assertRedirect(route('payment.pending', $payment));
        $this->actingAs($guardian)->post($this->checkoutUrl($payment), $payload)
            ->assertRedirect(route('payment.pending', $payment));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['data']['attributes']['line_items'][0]['amount'] === 12900
            && (int) $request['data']['attributes']['metadata']['user_id'] === $guardian->id
            && (int) $request['data']['attributes']['metadata']['subscription_id'] === $payment->subscription_id);
        $this->assertSame('129.00', $payment->fresh()->amount);
        $this->assertFalse($dependent->fresh()->isPremium());
    }

    public function test_browser_return_polling_and_history_keep_unpaid_gifts_pending(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $response = $this->paidApiResponse($payment);
        $response['data']['attributes']['payments'] = [];
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($response)]);
        $this->actingAs($guardian)->get(route('payment.paymongo.success', [
            'subscription' => $payment->subscription_id, 'gift_payment' => $payment->id,
        ]))->assertRedirect();
        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'pending');
        $this->actingAs($guardian)->get(route('payment.history'))->assertOk();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertFalse($dependent->fresh()->isPremium());
    }

    public function test_expired_provider_session_releases_the_dependent_purchase_lock(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $response = $this->paidApiResponse($payment);
        $response['data']['attributes']['status'] = 'expired';
        $response['data']['attributes']['payments'] = [];
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($response)]);

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertFalse(GuardianSubscriptionGiftService::unresolvedPayments($dependent)->exists());
        $this->assertFalse($dependent->fresh()->isPremium());
        $this->assertFalse($guardian->fresh()->isPremium());
        $this->actingAs($guardian)->get(route('payment.pending', $payment))->assertOk()
            ->assertSee('Payment Cancelled')->assertSee('Choose a subscription again')
            ->assertDontSee('function poll()');
        $replacement = $this->prepareGift($guardian, $dependent, $plan);
        $this->assertNotSame($payment->id, $replacement->id);
        $this->assertSame('129.00', $payment->fresh()->amount);
    }

    public function test_failed_attempt_in_an_active_session_keeps_one_payable_gift(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $response = $this->paidApiResponse($payment);
        $response['data']['attributes']['payments'][0]['attributes']['status'] = 'failed';
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($response)]);

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'pending');
        $this->assertTrue(GuardianSubscriptionGiftService::unresolvedPayments($dependent)->exists());
        $this->assertSame($payment->id, $this->prepareGift($guardian, $dependent, $plan)->id);
        $this->assertFalse($dependent->fresh()->isPremium());
    }

    public static function invalidExpiredSessions(): array
    {
        return [['data.id', 'cs_other'], ['data.attributes.livemode', true]];
    }

    #[DataProvider('invalidExpiredSessions')]
    public function test_mismatched_expired_session_cannot_release_a_payable_gift(string $path, mixed $value): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $response = $this->paidApiResponse($payment);
        $response['data']['attributes']['status'] = 'expired';
        $response['data']['attributes']['payments'] = [];
        data_set($response, $path, $value);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($response)]);

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'pending');
        $this->assertTrue(GuardianSubscriptionGiftService::unresolvedPayments($dependent)->exists());
    }

    public function test_expired_gift_renewal_preserves_the_existing_paid_term(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $subscription = app(SubscriptionService::class)->create($dependent, $plan);
        $subscription->payments()->sole()->update(['status' => PaymentStatus::Completed]);
        $originalEnd = $subscription->fresh()->ends_at->toIso8601String();
        $this->travelTo($subscription->ends_at->copy()->subDays(2));
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $response = $this->paidApiResponse($payment);
        $response['data']['attributes']['status'] = 'expired';
        $response['data']['attributes']['payments'] = [];
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($response)]);

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertSame($originalEnd, $subscription->ends_at->toIso8601String());
        $this->assertFalse(app(SubscriptionService::class)->isGiftManaged($subscription));
        $this->assertTrue($dependent->fresh()->isPremium());
    }

    public function test_unpaid_gift_renewal_preserves_regular_subscription_controls(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $subscription = app(SubscriptionService::class)->create($dependent, $plan);
        $subscription->payments()->sole()->update(['status' => PaymentStatus::Completed]);
        $this->travelTo($subscription->fresh()->ends_at->copy()->subDays(2));
        $gift = $this->prepareGift($guardian, $dependent, $plan);
        $this->assertSame($subscription->id, $gift->subscription_id);
        $this->assertSame(PaymentStatus::Pending, $gift->status);
        $this->assertNull(data_get($gift->payment_details, 'gift.applied_at'));

        $this->actingAs($dependent)->get(route('subscription.index'))->assertOk()
            ->assertSee('Renew via Checkout')->assertSee($plan->name);
        $this->actingAs($dependent)->post(route('subscription.cancel'), ['cancellation_reason' => 'Stop regular renewal'])
            ->assertRedirect(route('subscription.index'));
        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->fresh()->status);
    }

    public function test_regular_renewal_does_not_reuse_or_rewrite_a_pending_gift_payment(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $subscription = app(SubscriptionService::class)->create($dependent, $plan);
        $subscription->payments()->sole()->update(['status' => PaymentStatus::Completed]);
        $this->travelTo($subscription->fresh()->ends_at->copy()->subDays(2));
        $originalPrice = $plan->planPrices()->sole();
        $originalPrice->update(['is_default' => false]);
        $originalPrice->replicate()->fill(['is_default' => true, 'amount_minor' => 29900])->save();
        $gift = $this->prepareGift($guardian, $dependent, $plan);
        $before = $gift->only(['user_id', 'amount', 'payment_details']);

        $this->actingAs($dependent)->post(route('subscription.renew'))->assertRedirect();
        $this->assertSame($before, $gift->fresh()->only(['user_id', 'amount', 'payment_details']));
        $this->assertSame(2, Payment::count());
    }

    public function test_regular_renewal_completes_after_an_unpaid_gift_expires(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $subscription = app(SubscriptionService::class)->create($dependent, $plan);
        $subscription->payments()->sole()->update(['status' => PaymentStatus::Completed]);
        $originalEnd = $subscription->fresh()->ends_at->copy();
        $this->travelTo($originalEnd->copy()->subDays(2));
        $gift = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($gift);
        $response = $this->paidApiResponse($gift);
        $response['data']['attributes']['status'] = 'expired';
        $response['data']['attributes']['payments'] = [];
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($response)]);
        $this->actingAs($guardian)->getJson(route('payment.status', $gift))->assertJsonPath('status', 'cancelled');
        $closedDetails = $gift->fresh()->payment_details;

        $this->actingAs($dependent)->post(route('subscription.renew'))
            ->assertRedirect(route('payment.create', $subscription));
        $renewal = $subscription->payments()->where('user_id', $dependent->id)
            ->where('status', PaymentStatus::Pending)->sole();
        $renewal->update(['status' => PaymentStatus::Completed]);

        $this->assertTrue($subscription->fresh()->ends_at->equalTo($originalEnd->addMonth()));
        $this->assertSame($closedDetails, $gift->fresh()->payment_details);
        $this->assertSame(PaymentStatus::Cancelled, $gift->fresh()->status);
        $this->assertFalse(app(SubscriptionService::class)->isGiftManaged($subscription));
    }

    public static function staleRegularLifecycleEntryPoints(): array
    {
        return [['expire'], ['reconcileLifecycleState']];
    }

    #[DataProvider('staleRegularLifecycleEntryPoints')]
    public function test_stale_regular_expiry_cannot_overwrite_a_newer_paid_term(string $entryPoint): void
    {
        [, $dependent, $plan] = $this->family();
        $subscription = app(SubscriptionService::class)->create($dependent, $plan);
        $subscription->payments()->sole()->update(['status' => PaymentStatus::Completed]);
        $stale = $subscription->fresh();
        $this->travelTo($stale->ends_at->copy()->addDay());
        $extendedEnd = now()->addMonth();
        Subscription::whereKey($stale->id)->update(['ends_at' => $extendedEnd, 'end_date' => $extendedEnd]);

        app(SubscriptionService::class)->{$entryPoint}($stale);

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertTrue($subscription->ends_at->equalTo($extendedEnd));
    }

    public static function unavailableCheckoutPlans(): array
    {
        return [['inactive'], ['archived'], ['not_started'], ['ended'], ['disabled_price']];
    }

    #[DataProvider('unavailableCheckoutPlans')]
    public function test_admin_unavailable_plan_is_rejected_before_gateway_session_creation(string $change): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        match ($change) {
            'inactive' => $plan->update(['is_active' => false]),
            'archived' => $plan->update(['archived_at' => now()]),
            'not_started' => $plan->update(['availability_starts_on' => now()->addDay()]),
            'ended' => $plan->update(['availability_ends_on' => now()->subDay()]),
            'disabled_price' => $plan->planPrices()->update(['is_active' => false]),
        };
        Http::fake(['api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => ['id' => 'cs_unavailable', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/unavailable']],
        ])]);

        $this->actingAs($guardian)->post($this->checkoutUrl($payment), [
            'accept_terms' => true, 'payment_method' => 'gcash', 'billing_email' => $guardian->email,
        ])->assertSessionHasErrors('plan_id');
        Http::assertNothingSent();
        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
        $this->assertFalse(GuardianSubscriptionGiftService::unresolvedPayments($dependent)->exists());
    }

    public static function apiConfirmationEntryPoints(): array
    {
        return [['return'], ['pending'], ['poll'], ['history']];
    }

    #[DataProvider('apiConfirmationEntryPoints')]
    public function test_api_confirmation_grants_only_dependent_once_without_webhook(string $entryPoint): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        config(['paymongo.webhook_secret' => null]);
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($this->paidApiResponse($payment))]);

        $url = match ($entryPoint) {
            'return' => route('payment.paymongo.success', [
                'subscription' => $payment->subscription_id, 'gift_payment' => $payment->id,
            ]),
            'pending' => route('payment.pending', $payment),
            'poll' => route('payment.status', $payment),
            'history' => route('payment.history'),
        };
        $response = $this->actingAs($guardian)->get($url);
        if ($entryPoint === 'poll') {
            $response->assertOk()->assertJsonPath('status', 'completed');
        } elseif ($entryPoint === 'history') {
            $response->assertOk();
        } else {
            $response->assertRedirect(route('parent.children.index'));
        }

        $payment->refresh();
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertTrue($dependent->fresh()->isPremium());
        $this->assertFalse($guardian->fresh()->isPremium());
        $this->assertSame($guardian->id, $payment->user_id);
        $this->assertSame($dependent->id, $payment->subscription->user_id);
        $this->assertSame('2026-12-10 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertTrue(data_get($payment->payment_details, 'verified_via_api'));
        $this->assertNull(data_get($payment->payment_details, 'gift_webhook_event_id'));

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'completed');
        config(['paymongo.webhook_secret' => 'gift_webhook_secret']);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $this->assertSame('2026-12-10 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(SendPaymentReceiptEmail::class, 1);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://api.paymongo.com/v1/checkout_sessions/cs_gift_'.$payment->id);
    }

    public static function invalidApiConfirmations(): array
    {
        return [
            'wrong session' => ['data.id', 'cs_other'],
            'wrong payment' => ['data.attributes.metadata.payment_id', '999999'],
            'wrong purchaser' => ['data.attributes.metadata.user_id', '999999'],
            'wrong beneficiary subscription' => ['data.attributes.metadata.subscription_id', '999999'],
            'wrong amount' => ['data.attributes.payments.0.attributes.amount', 1],
            'wrong currency' => ['data.attributes.payments.0.attributes.currency', 'USD'],
            'wrong mode' => ['data.attributes.livemode', true],
            'not paid' => ['data.attributes.payments.0.attributes.status', 'failed'],
            'no payments' => ['data.attributes.payments', []],
        ];
    }

    #[DataProvider('invalidApiConfirmations')]
    public function test_api_confirmation_rejects_unpaid_or_mismatched_purchase(string $path, mixed $value): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $response = $this->paidApiResponse($payment);
        data_set($response, $path, $value);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($response)]);

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'pending');
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertFalse($dependent->fresh()->isPremium());
        $this->assertNull(data_get($payment->payment_details, 'gift.applied_at'));
    }

    public function test_api_failure_keeps_gift_pending_and_allows_later_confirmation(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::sequence()
            ->push([], 503)->push($this->paidApiResponse($payment))]);

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'pending');
        $this->assertFalse($dependent->fresh()->isPremium());
        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'completed');
        $this->assertTrue($dependent->fresh()->isPremium());
    }

    public function test_api_confirmation_cannot_be_triggered_by_the_dependent_or_an_unrelated_user(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($this->paidApiResponse($payment))]);
        $this->actingAs($dependent)->getJson(route('payment.status', $payment))->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson(route('payment.status', $payment))->assertForbidden();
        Http::assertNothingSent();
        $this->assertFalse($dependent->fresh()->isPremium());
    }

    public function test_same_plan_api_purchase_extends_from_existing_expiry_using_the_frozen_term(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $first = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($first);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/cs_gift_'.$first->id => Http::response($this->paidApiResponse($first))]);
        $this->actingAs($guardian)->getJson(route('payment.status', $first))
            ->assertOk()->assertJsonPath('status', 'completed');

        $this->travelTo(Carbon::parse('2026-12-04 10:00:00'));
        $second = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($second);
        $plan->planPrices()->update(['duration_count' => 12, 'amount_minor' => 99900]);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/cs_gift_'.$second->id => Http::response($this->paidApiResponse($second))]);
        $this->actingAs($guardian)->getJson(route('payment.status', $second))
            ->assertOk()->assertJsonPath('status', 'completed');

        $this->assertSame($first->subscription_id, $second->subscription_id);
        $this->assertSame('129.00', $second->fresh()->amount);
        $this->assertSame('2027-01-10 10:00:00', $second->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertFalse($second->subscription->auto_renew);
        $this->assertFalse($guardian->fresh()->isPremium());
    }

    #[DataProvider('apiConfirmationEntryPoints')]
    public function test_original_payer_can_confirm_an_in_flight_gift_after_relationship_removal(string $entryPoint): void
    {
        [$guardian, $dependent, $plan, $link] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $link->delete();
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($this->paidApiResponse($payment))]);

        $url = match ($entryPoint) {
            'return' => route('payment.paymongo.success', [
                'subscription' => $payment->subscription_id, 'gift_payment' => $payment->id,
            ]),
            'pending' => route('payment.pending', $payment),
            'poll' => route('payment.status', $payment),
            'history' => route('payment.history'),
        };
        $response = $this->actingAs($guardian)->get($url);
        $this->assertLessThan(400, $response->status());
        $this->assertSame(PaymentStatus::Completed, $payment->fresh()->status);
        $this->assertTrue($dependent->fresh()->isPremium());
        $this->actingAs($guardian)->get($this->checkoutUrl($payment))->assertForbidden();
    }

    public function test_history_retries_api_follow_up_failure_without_another_paid_term(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        Http::fake(['api.paymongo.com/v1/checkout_sessions/*' => Http::response($this->paidApiResponse($payment))]);
        \Illuminate\Support\Facades\Event::forget(\App\Events\PaymentSuccessful::class);
        $fail = true;
        \Illuminate\Support\Facades\Event::listen(\App\Events\PaymentSuccessful::class, function ($event) use (&$fail) {
            if ($fail) {
                $fail = false;
                throw new \RuntimeException('Queue unavailable before dispatch');
            }
            (new \App\Listeners\HandlePaymentSuccessful)->handle($event);
        });

        $this->actingAs($guardian)->getJson(route('payment.status', $payment))
            ->assertOk()->assertJsonPath('status', 'completed');
        $this->assertTrue($dependent->fresh()->isPremium());
        Bus::assertNotDispatched(SendPaymentReceiptEmail::class);
        $this->actingAs($guardian)->get(route('payment.history'))->assertOk();
        Bus::assertDispatchedTimes(SendPaymentReceiptEmail::class, 1);
        Bus::assertDispatchedTimes(SendSubscriptionWelcomeEmail::class, 1);
        $this->assertSame('2026-12-10 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        Http::assertSentCount(1);
    }

    public function test_gift_success_callback_does_not_flash_the_verification_notice(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);

        $response = $this->actingAs($guardian)->get(route('payment.paymongo.success', [
            'subscription' => $payment->subscription_id, 'gift_payment' => $payment->id,
        ]));

        $response->assertRedirect(route('payment.pending', $payment));
        $response->assertSessionMissing('info');
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_provider_creation_timeout_does_not_create_a_second_checkout(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Uncertain provider timeout'));
        $payload = ['accept_terms' => true, 'payment_method' => 'gcash'];
        $this->actingAs($guardian)->post($this->checkoutUrl($payment), $payload)->assertRedirect();
        $this->assertSame('unknown', data_get($payment->fresh()->payment_details, 'gift.checkout_state'));
        Http::fake(fn () => throw new \RuntimeException('Must not retry an unknown creation'));
        $this->actingAs($guardian)->post($this->checkoutUrl($payment), $payload)->assertRedirect();
        $this->assertSame('unknown', data_get($payment->fresh()->payment_details, 'gift.checkout_state'));
        $this->assertSame(1, Payment::count());
    }

    public function test_signed_paid_checkout_grants_only_dependent_and_applies_once_across_event_ids(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $event = $this->paidEvent($payment);

        $this->signedWebhook($event)->assertOk();
        $this->assertSame(PaymentStatus::Completed, $payment->fresh()->status);
        $this->assertSame('2026-12-09 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertTrue($dependent->fresh()->isPremium());
        $this->assertFalse($guardian->fresh()->isPremium());
        $this->assertFalse($payment->subscription->fresh()->auto_renew);
        $this->assertNotNull(data_get($payment->fresh()->payment_details, 'gift.applied_at'));

        $this->signedWebhook($event)->assertOk();
        $event['data']['id'] = 'evt_second_delivery';
        $this->signedWebhook($event)->assertOk();
        $this->assertSame('2026-12-09 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(1, Payment::count());
    }

    public function test_manual_same_plan_purchase_extends_from_existing_expiry(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $first = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($first);
        $this->signedWebhook($this->paidEvent($first))->assertOk();

        $this->travelTo(Carbon::parse('2026-12-03 10:00:00'));
        $second = $this->prepareGift($guardian, $dependent, $plan);
        $this->assertSame($first->subscription_id, $second->subscription_id);
        $this->assertSame('2026-12-09 10:00:00', $second->subscription->ends_at->format('Y-m-d H:i:s'));
        $this->attachSession($second);
        $this->signedWebhook($this->paidEvent($second))->assertOk();
        $this->assertSame('2027-01-09 10:00:00', $second->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(2, Payment::count());
    }

    public function test_different_active_plan_is_blocked_and_expired_access_can_be_bought_again(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $first = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($first);
        $this->signedWebhook($this->paidEvent($first))->assertOk();
        $otherPlan = $plan->replicate();
        $otherPlan->slug = 'different-gift-plan';
        $otherPlan->save();
        $price = $plan->defaultPlanPrice->replicate();
        $price->plan_id = $otherPlan->id;
        $price->save();
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $otherPlan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');
        $this->assertSame(1, Payment::count());

        $this->travelTo(Carbon::parse('2027-01-01 10:00:00'));
        $next = $this->prepareGift($guardian, $dependent, $otherPlan);
        $this->attachSession($next);
        $this->signedWebhook($this->paidEvent($next))->assertOk();
        $this->assertNotSame($first->subscription_id, $next->subscription_id);
        $this->assertSame('2027-02-01 10:00:00', $next->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(2, Subscription::count());
    }

    public function test_unsigned_mismatched_amount_and_wrong_session_cannot_grant_access(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $event = $this->paidEvent($payment);
        $this->postJson(route('webhook.paymongo'), $event)->assertUnauthorized();
        $event['data']['attributes']['data']['attributes']['payments'][0]['attributes']['amount'] = 1;
        $this->signedWebhook($event)->assertStatus(400);
        $event = $this->paidEvent($payment);
        $event['data']['attributes']['data']['id'] = 'cs_someone_else';
        $this->signedWebhook($event)->assertStatus(400);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertFalse($dependent->fresh()->isPremium());
    }

    public function test_failed_transaction_can_retry_same_webhook_without_lost_payment_or_duplicate_term(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $failOnce = true;
        Subscription::updating(function (Subscription $subscription) use (&$failOnce) {
            if ($failOnce && $subscription->isDirty('ends_at')) {
                $failOnce = false;
                throw new \RuntimeException('Temporary persistence failure');
            }
        });
        $event = $this->paidEvent($payment);
        $this->travelTo(Carbon::parse('2026-11-09 11:00:00'));
        $this->signedWebhook($event)->assertStatus(500);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertNull(data_get($payment->payment_details, 'gift.applied_at'));
        $this->signedWebhook($event)->assertOk();
        $this->assertTrue($dependent->fresh()->isPremium());
    }

    public function test_activation_uses_purchased_duration_even_if_catalogue_changes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $plan->planPrices()->update(['duration_count' => 12, 'amount_minor' => 99900]);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $this->assertSame('2026-12-09 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('129.00', $payment->fresh()->amount);
    }

    public function test_gift_is_non_refundable_and_dependent_financial_view_is_read_only(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $plan->update(['description' => 'Learning benefits for this dependent', 'features' => ['text_translator' => true]]);
        SubscriptionPlan::create([
            'name' => 'Other Learner Benefits', 'slug' => 'other-learner-benefits', 'price' => 299,
            'plan_audience' => 'learner', 'is_active' => true, 'features' => ['voice_speech_translator' => true],
        ]);
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $payment->update(['payment_details' => array_merge((array) $payment->payment_details, [
            'billing' => ['email' => 'private-payer@example.test', 'phone' => '09123456789'],
        ])]);
        $this->attachSession($payment);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $response = $this->actingAs($dependent)->get(route('subscription.index'))->assertOk()
            ->assertSee('Managed by your guardian')->assertDontSee('private-payer@example.test')
            ->assertDontSee($payment->transaction_id)->assertDontSee('09123456789')
            ->assertSee('Subscription plans and benefits')->assertSee('Learning benefits for this dependent')
            ->assertSee('Other Learner Benefits')->assertSee('Text Translator')
            ->assertSee('Compare all plan features')->assertSee('Current plan')
            ->assertDontSee('Continue to payment process')->assertDontSee('Renew via Checkout')
            ->assertDontSee('href="'.route('payment.history').'"', false);
        $this->assertNotEmpty($response->viewData('comparisonFeatures'));
        foreach ($response->viewData('planCards') as $card) {
            $this->assertFalse($card['is_eligible']);
        }
        $this->actingAs($dependent)->post(route('subscription.refund'))->assertForbidden();
        $this->actingAs($dependent)->post(route('subscription.cancel'), ['cancellation_reason' => 'test'])->assertForbidden();
        $this->actingAs($dependent)->post(route('subscription.renew'))->assertForbidden();
        $this->assertTrue($dependent->fresh()->isPremium());
        $this->actingAs($guardian)->get(route('payment.receipt', $payment))->assertOk();
    }

    public function test_revocation_blocks_management_but_keeps_paid_access_and_payer_receipt(): void
    {
        [$guardian, $dependent, $plan, $link] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $link->delete();
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $this->assertTrue($dependent->fresh()->isPremium());
        $this->actingAs($guardian)->get($this->checkoutUrl($payment))->assertForbidden();
        $this->actingAs($guardian)->get(route('payment.receipt', $payment))->assertOk();
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertForbidden();
    }

    public function test_failed_gift_does_not_change_existing_access_or_create_automatic_retries(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $subscription = app(SubscriptionService::class)->create($dependent, $plan);
        $subscription->payments()->sole()->update(['status' => PaymentStatus::Completed]);
        $beforeEnd = $subscription->fresh()->ends_at->format('Y-m-d H:i:s');
        $this->travelTo(Carbon::parse($beforeEnd)->subDays(2));
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $payment->update(['status' => PaymentStatus::Failed]);
        app(SubscriptionDunningService::class)->handleFailedPayment($payment);
        app(SubscriptionDunningService::class)->processRetryPayments();
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertSame($beforeEnd, $subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(2, Payment::count());
    }

    public function test_scheduler_does_not_discard_unresolved_gift_payment(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $payment->forceFill(['created_at' => now()->subDays(9)])->saveQuietly();
        $this->artisan('subscriptions:process-renewals')->assertSuccessful();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(1, Payment::count());
    }

    public function test_stale_expiry_and_reconciliation_cannot_erase_extended_gift_access(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $stale = $payment->subscription->fresh();
        $this->travelTo(Carbon::parse('2026-12-10 10:00:00'));
        Subscription::whereKey($stale->id)->update(['ends_at' => '2027-01-09 10:00:00', 'end_date' => '2027-01-09']);
        app(SubscriptionService::class)->expire($stale);
        app(SubscriptionService::class)->reconcileLifecycleState($stale);
        $this->assertSame(SubscriptionStatus::Active, $stale->fresh()->status);
        $this->assertSame('2027-01-09 10:00:00', $stale->fresh()->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_my_dependents_exposes_dynamic_gift_action_without_the_legacy_explanatory_panel(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $url = route('parent.children.subscription-gift.create', $dependent);
        $this->actingAs($guardian)->get(route('parent.children.index'))->assertOk()
            ->assertSee($url, false)->assertSee('Gift Subscription');
        $this->actingAs($guardian)->get($url)->assertOk()->assertSee('Gift Monthly')
            ->assertSee('129.00')->assertSee('non-refundable')
            ->assertDontSee('One prepaid term for this dependent')
            ->assertDontSee('A different plan cannot replace an active paid plan')
            ->assertDontSee('No paid learner plans are currently available');
    }

    public function test_gift_page_reuses_legacy_learner_catalogue_and_hides_old_empty_state_copy(): void
    {
        [$guardian, $dependent] = $this->family();
        SubscriptionPlan::create([
            'name' => 'Basic Legacy', 'slug' => 'basic-legacy', 'description' => 'Admin-managed basic access',
            'price' => 199, 'plan_audience' => 'learner', 'billing_mode' => 'monthly',
            'is_active' => true, 'sort_order' => 1, 'features' => ['learning' => ['basic_lessons' => true]],
        ]);

        $giftPage = $this->actingAs($guardian)->get(route('parent.children.subscription-gift.create', $dependent));
        $giftPage
            ->assertOk()
            ->assertSee('Choose a subscription for '.$dependent->name)
            ->assertSee('Basic Legacy')
            ->assertSee('199.00')
            ->assertSee('Monthly')
            ->assertSee('Basic Lessons')
            ->assertSee('Gift This Plan')
            ->assertDontSee('One prepaid term for this dependent')
            ->assertDontSee('A different plan cannot replace an active paid plan')
            ->assertDontSee('No paid learner plans are currently available');

        $legacyPlan = SubscriptionPlan::query()->where('slug', 'basic-legacy')->firstOrFail();
        $legacyPlan->update(['name' => 'Basic Legacy Updated', 'price' => 249, 'description' => 'Updated admin copy']);

        $this->actingAs($guardian)->get(route('parent.children.subscription-gift.create', $dependent))
            ->assertOk()
            ->assertSee('Basic Legacy Updated')
            ->assertSee('Updated admin copy')
            ->assertSee('249.00');

        $this->actingAs($dependent)->get(route('subscription.index'))
            ->assertOk()
            ->assertSee('Basic Legacy Updated')
            ->assertSee('249.00')
            ->assertSee('Updated admin copy');
    }

    public function test_gift_comparison_matches_regular_subscription_baseline_inheritance(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $plan->update(['features' => ['unlimited_quiz_shields', 'text_translator']]);
        SubscriptionPlan::create([
            'name' => 'Learner Free', 'slug' => 'learner-free', 'price' => 0,
            'plan_audience' => 'learner', 'is_active' => true, 'sort_order' => -1,
            'features' => ['modules', 'quizzes', 'support'],
        ]);
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();

        $giftResponse = $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk();
        $regularResponse = $this->actingAs($guardian)->get(route('subscription.index'))->assertOk();
        $giftCards = collect($giftResponse->viewData('planCards'))->keyBy('id');
        $regularCards = collect($regularResponse->viewData('planCards'))->keyBy('id');
        $baselineKeys = [
            'free_core_learning', 'free_quiz_shields', 'free_username_changes',
            'free_certificate_downloads', 'free_progress_tracking', 'free_upgrade_anytime',
        ];
        foreach ($giftCards as $id => $card) {
            $this->assertSame($regularCards[$id]['feature_keys'], $card['feature_keys']);
            foreach ($baselineKeys as $key) {
                $this->assertContains($key, $card['feature_keys']);
            }
        }
        $this->assertSame($regularResponse->viewData('comparisonFeatures'), $giftResponse->viewData('comparisonFeatures'));
        $this->assertTrue($giftCards[$plan->id]['is_current']);
        $giftResponse->assertSee('Detailed matrix with Free baseline and premium plan differences');
    }

    public function test_gift_comparison_reflects_admin_feature_updates_without_duplicate_baseline_rows(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        config(['subscription_features.labels.duplicate_core' => 'Access to core age-appropriate learning modules']);
        $plan->update(['features' => ['duplicate_core', 'text_translator']]);
        $first = $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk();
        $rows = collect($first->viewData('comparisonFeatures'));
        $this->assertCount(1, $rows->where('label', 'Access to core age-appropriate learning modules'));
        $paid = collect($first->viewData('planCards'))->firstWhere('id', $plan->id);
        $this->assertContains('free_core_learning', $paid['feature_keys']);
        $this->assertNotContains('duplicate_core', $paid['feature_keys']);

        $plan->update(['features' => ['new_admin_feature']]);
        $updated = $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk();
        $rows = collect($updated->viewData('comparisonFeatures'));
        $this->assertContains('New Admin Feature', $rows->pluck('label')->all());
        $paid = collect($updated->viewData('planCards'))->firstWhere('id', $plan->id);
        $this->assertNotContains('text_translator', $paid['feature_keys']);
        $this->assertContains('free_core_learning', $paid['feature_keys']);
    }

    public function test_gift_comparison_reuses_regular_free_baseline_when_no_active_free_plan_exists(): void
    {
        [$guardian, $dependent] = $this->family();
        SubscriptionPlan::query()->where('price', 0)->update(['is_active' => false]);

        $gift = $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk();
        $regular = $this->actingAs($guardian)->get(route('subscription.index'))->assertOk();
        $giftData = $gift->original->getData();
        $giftCards = collect($giftData['comparisonPlanCards'] ?? $giftData['planCards']);
        $regularCards = collect($regular->viewData('planCards'));
        $this->assertSame($regularCards->pluck('name')->all(), $giftCards->pluck('name')->all());
        $this->assertTrue($giftCards->first()['is_baseline']);
        $this->assertFalse($giftCards->first()['is_eligible']);
        $this->assertSame($regular->viewData('comparisonFeatures'), $gift->viewData('comparisonFeatures'));
    }

    public function test_active_dependent_plan_is_current_and_cannot_be_purchased_again_before_renewal_window(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();

        $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk()
            ->assertSee('Active Subscription')
            ->assertSee('Current Plan:')
            ->assertSee('Gift Monthly')
            ->assertSee('Current plan')
            ->assertSee('Paid access expires')
            ->assertDontSee('Gift This Plan')
            ->assertDontSee('Continue to Gift Checkout')
            ->assertDontSee('Renew via Checkout')
            ->assertDontSee('You remain the purchaser');
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');
        $this->assertSame(1, Payment::count());
        $this->assertSame('2026-12-10 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_gift_renewal_uses_the_existing_admin_configured_window(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $plan->update(['renewal_warning_days' => 3]);
        $first = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($first);
        $this->signedWebhook($this->paidEvent($first))->assertOk();

        $this->travelTo(Carbon::parse('2026-12-06 10:00:00'));
        $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk()
            ->assertDontSee('Renew via Checkout');
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');

        $this->travelTo(Carbon::parse('2026-12-07 10:00:00'));
        $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk()
            ->assertSee('Your dependent\'s premium access is expiring soon', false)
            ->assertSee('Renew via Checkout')
            ->assertSee('Current plan')
            ->assertDontSee('Gift This Plan');
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertRedirect();
        $this->assertSame(2, Payment::count());
    }

    public function test_expired_dependent_can_select_plans_again(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();

        $this->travelTo(Carbon::parse('2026-12-11 10:00:00'));
        $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk()
            ->assertDontSee('Active Subscription')
            ->assertSee('Gift This Plan')
            ->assertSee('Continue to Gift Checkout');
    }

    public function test_gift_checkout_omits_the_extra_notice_but_identifies_the_recipient(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->actingAs($guardian)->get($this->checkoutUrl($payment))->assertOk()
            ->assertSee('Gift Dependent')
            ->assertDontSee('Paid by Gift Guardian')
            ->assertDontSee('One prepaid term; no automatic renewal.')
            ->assertDontSee('Gift subscription payments are final and non-refundable.');
    }

    public function test_unstarted_gift_renewal_checkout_rechecks_the_current_renewal_window(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $first = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($first);
        $this->signedWebhook($this->paidEvent($first))->assertOk();
        $this->travelTo(Carbon::parse('2026-12-04 10:00:00'));
        $renewal = $this->prepareGift($guardian, $dependent, $plan);
        $renewal->subscription->update(['ends_at' => now()->addMonth(), 'end_date' => now()->addMonth()]);
        $this->actingAs($guardian)->post($this->checkoutUrl($renewal), [
            'accept_terms' => true, 'payment_method' => 'gcash',
        ])->assertSessionHasErrors('plan_id');
        Http::assertNothingSent();
    }

    public static function overlappingGiftSubscriptions(): array
    {
        return [['longer_same_plan'], ['conflicting_plan']];
    }

    #[DataProvider('overlappingGiftSubscriptions')]
    public function test_gift_renewal_banner_uses_all_current_subscription_records(string $scenario): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $other = $payment->subscription->fresh()->replicate();
        if ($scenario === 'conflicting_plan') {
            $otherPlan = $plan->replicate();
            $otherPlan->slug = 'overlapping-plan';
            $otherPlan->save();
            $other->plan_id = $otherPlan->id;
            $this->travelTo(Carbon::parse('2026-12-04 10:00:00'));
        }
        $other->ends_at = now()->addDays(2);
        $other->end_date = $other->ends_at;
        $other->save();

        $this->actingAs($guardian)->get($this->purchaseUrl($dependent))->assertOk()
            ->assertSee('Current plan')->assertDontSee('Renew via Checkout');
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');
        $this->assertSame(1, Payment::count());
    }

    public function test_unstarted_gift_checkout_blocks_a_competing_plan_activated_after_selection(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $otherPlan = $plan->replicate();
        $otherPlan->slug = 'newly-active-plan';
        $otherPlan->save();
        $dependent->subscriptions()->create([
            'plan_id' => $otherPlan->id, 'plan' => $otherPlan->slug, 'status' => SubscriptionStatus::Active,
            'start_date' => now(), 'starts_at' => now(), 'ends_at' => now()->addMonth(),
            'end_date' => now()->addMonth(), 'price_paid' => 129, 'auto_renew' => false,
        ]);

        $this->actingAs($guardian)->post($this->checkoutUrl($payment), [
            'accept_terms' => true, 'payment_method' => 'gcash',
        ])->assertSessionHasErrors('plan_id');
        Http::assertNothingSent();
    }

    public function test_unstarted_gift_renewal_cannot_bypass_a_longer_same_plan_subscription(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $first = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($first);
        $this->signedWebhook($this->paidEvent($first))->assertOk();
        $this->travelTo(Carbon::parse('2026-12-04 10:00:00'));
        $renewal = $this->prepareGift($guardian, $dependent, $plan);
        $longer = $first->subscription->fresh()->replicate();
        $longer->ends_at = now()->addMonth();
        $longer->end_date = $longer->ends_at;
        $longer->save();

        $this->actingAs($guardian)->post($this->checkoutUrl($renewal), [
            'accept_terms' => true, 'payment_method' => 'gcash',
        ])->assertSessionHasErrors('plan_id');
        Http::assertNothingSent();
    }

    public function test_legacy_catalogue_price_is_frozen_into_gift_payment(): void
    {
        [$guardian, $dependent] = $this->family();
        $legacyPlan = SubscriptionPlan::create([
            'name' => 'Admin Basic', 'slug' => 'admin-basic', 'description' => 'Basic admin plan',
            'price' => 199, 'plan_audience' => 'learner', 'billing_mode' => 'monthly',
            'is_active' => true, 'sort_order' => 1, 'features' => [],
        ]);

        $response = $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $legacyPlan->id, 'request_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect();
        $payment = Payment::query()->latest('id')->firstOrFail();
        $this->assertSame(199.0, (float) $payment->amount);
        $this->assertSame(19900, data_get($payment->payment_details, 'gift.amount_minor'));
        $this->assertSame('month', data_get($payment->payment_details, 'gift.duration_unit'));
        $this->assertSame('Admin Basic', data_get($payment->payment_details, 'gift.plan_name'));

        $legacyPlan->update(['name' => 'Changed Basic', 'price' => 999]);
        $this->assertSame(19900, data_get($payment->fresh()->payment_details, 'gift.amount_minor'));
        $this->assertSame('Admin Basic', data_get($payment->fresh()->payment_details, 'gift.plan_name'));
    }

    public function test_unavailable_and_inactive_plans_cannot_be_selected_for_a_gift(): void
    {
        [$guardian, $dependent, $activePlan] = $this->family();
        $futurePlan = $activePlan->replicate();
        $futurePlan->slug = 'future-gift-plan';
        $futurePlan->name = 'Future Gift Plan';
        $futurePlan->availability_starts_on = now()->addDay();
        $futurePlan->save();
        $futurePlan->planPrices()->create([
            'duration_mode' => 'preset', 'duration_unit' => 'month', 'duration_count' => 1,
            'duration_label' => 'One month', 'amount_minor' => 19900, 'currency' => 'PHP',
            'is_default' => true, 'is_active' => true,
        ]);

        $inactivePlan = $activePlan->replicate();
        $inactivePlan->slug = 'inactive-gift-plan';
        $inactivePlan->name = 'Inactive Gift Plan';
        $inactivePlan->is_active = false;
        $inactivePlan->save();

        $this->actingAs($guardian)->get($this->purchaseUrl($dependent))
            ->assertOk()
            ->assertSee('Future Gift Plan')
            ->assertSee('Currently unavailable')
            ->assertDontSee('Inactive Gift Plan')
            ->assertSee('Gift This Plan');

        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $futurePlan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');
        $this->assertSame(0, Payment::count());
    }

    public function test_gift_checkout_ignores_inactive_plan_prices_when_resolving_current_amount(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $plan->planPrices()->update(['is_active' => false]);
        $plan->planPrices()->create([
            'duration_mode' => 'preset', 'duration_unit' => 'month', 'duration_count' => 1,
            'duration_label' => 'Current month', 'amount_minor' => 15900, 'currency' => 'PHP',
            'is_default' => true, 'is_active' => true,
        ]);

        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $payment = Payment::query()->sole();
        $this->assertSame(159.0, (float) $payment->amount);
        $this->assertSame(15900, data_get($payment->payment_details, 'gift.amount_minor'));
        $this->assertSame('Current month', data_get($payment->payment_details, 'gift.duration_label'));
    }

    public function test_disabling_the_last_plan_price_does_not_revive_legacy_pricing_for_gifts(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $plan->planPrices()->update(['is_active' => false]);
        $plan->update(['price' => 999]);

        $this->actingAs($guardian)->get($this->purchaseUrl($dependent))
            ->assertOk()
            ->assertSee('No active price configured');
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');
        $this->assertSame(0, Payment::count());
    }

    public function test_receipt_and_notification_identify_gift_and_keep_financial_links_with_payer(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $mail = new PaymentReceiptMail($payment->fresh());
        $this->assertStringContainsString('Gift', $mail->envelope()->subject);
        $frozenEndDate = Carbon::parse(data_get($payment->fresh()->payment_details, 'gift.after_expires_at'))->format('M d, Y');
        $payment->subscription->plan()->firstOrFail()->update(['name' => 'Renamed After Purchase']);
        $payment->subscription->update(['end_date' => now()->addYears(2), 'ends_at' => now()->addYears(2)]);
        $mailContent = $mail->content()->with;
        $this->assertSame('Gift Monthly', $mailContent['planName']);
        $this->assertSame($frozenEndDate, $mailContent['endDate']);
        $this->assertSame(route('payment.receipt', $payment), $mailContent['invoiceUrl']);
        $data = (new SubscriptionResultNotification('completed', $payment->subscription, $payment))->toDatabase($guardian);
        $this->assertStringContainsString('Gift Dependent', $data['message']);
        $this->assertSame(route('payment.receipt', $payment), $data['action_url']);
        Bus::assertDispatched(SendSubscriptionWelcomeEmail::class);
        Notification::assertSentTo($guardian, SubscriptionResultNotification::class);
        Notification::assertNotSentTo($dependent, SubscriptionResultNotification::class);
    }

    public function test_gift_requires_nonempty_stored_session_and_mode_currency_match(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $event = $this->paidEvent($payment);
        unset($event['data']['attributes']['data']['id']);
        $this->signedWebhook($event)->assertStatus(400);
        $this->attachSession($payment);
        $event = $this->paidEvent($payment);
        $event['data']['attributes']['livemode'] = true;
        $this->signedWebhook($event)->assertStatus(400);
        $event = $this->paidEvent($payment);
        $event['data']['attributes']['data']['attributes']['payments'][0]['attributes']['currency'] = 'USD';
        $this->signedWebhook($event)->assertStatus(400);
        $this->assertFalse($dependent->fresh()->isPremium());
    }

    public function test_pending_gift_blocks_ordinary_and_legacy_purchase_and_old_checkout(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $old = app(SubscriptionService::class)->create($dependent, $plan);
        $old->payments()->update(['status' => PaymentStatus::Failed]);
        $old->update(['status' => SubscriptionStatus::Cancelled]);
        $this->prepareGift($guardian, $dependent, $plan);
        foreach (['subscription.subscribe', 'subscription.process-upgrade'] as $route) {
            $this->actingAs($dependent)->post(route($route), ['plan_id' => $plan->id])
                ->assertSessionHas('error');
        }
        $this->actingAs($dependent)->post(route('payment.checkout.proceed', $old), [
            'accept_terms' => true, 'payment_method' => 'gcash',
        ])->assertSessionHasErrors('plan_id');
        try {
            app(SubscriptionService::class)->createLegacy($dependent, app(\App\Services\PayMongoPaymentLinkService::class));
            $this->fail('Legacy checkout must reject an unresolved gift.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('plan_id', $exception->errors());
        }
        $this->assertSame(2, Payment::count());
        Http::assertNothingSent();
    }

    public function test_pending_self_purchase_blocks_gift_preparation(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        app(SubscriptionService::class)->create($dependent, $plan);
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');
        $this->assertSame(1, Payment::count());
    }

    public function test_any_other_active_plan_blocks_gift_even_with_matching_active_access(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $matching = app(SubscriptionService::class)->create($dependent, $plan);
        $matching->payments()->sole()->update(['status' => PaymentStatus::Completed]);
        $otherPlan = $plan->replicate();
        $otherPlan->slug = 'historical-other-plan';
        $otherPlan->save();
        $conflict = $matching->fresh()->replicate();
        $conflict->plan_id = $otherPlan->id;
        $conflict->save();
        $this->actingAs($guardian)->post($this->purchaseUrl($dependent), [
            'plan_id' => $plan->id, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('plan_id');
        $this->assertSame(1, Payment::count());
    }

    public function test_legacy_webhook_and_admin_override_cannot_complete_a_gift(): void
    {
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        $event = $this->paidEvent($payment);
        $event['data']['attributes']['type'] = 'link.payment.paid';
        foreach ([true, false] as $withPaymentId) {
            if (! $withPaymentId) {
                unset($event['data']['attributes']['data']['attributes']['metadata']['payment_id']);
                $event['data']['id'] .= '_fallback';
            }
            $this->signedWebhook($event)->assertOk();
            $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        }
        try {
            app(\App\Http\Controllers\Admin\PaymentAdminController::class)->markAsCompleted($payment->fresh());
            $this->fail('A gift requires exact signed payment confirmation.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $payment->forceFill(['status' => PaymentStatus::Completed])->saveQuietly();
        $again = $this->prepareGift($guardian, $dependent, $plan);
        $this->assertSame($payment->id, $again->id);
        $this->assertNull(data_get($payment->fresh()->payment_details, 'gift.applied_at'));
        $this->assertFalse($dependent->fresh()->isPremium());
    }

    public function test_follow_up_dispatch_failure_can_retry_without_another_paid_term(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $payment = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($payment);
        \Illuminate\Support\Facades\Event::forget(\App\Events\PaymentSuccessful::class);
        $fail = true;
        \Illuminate\Support\Facades\Event::listen(\App\Events\PaymentSuccessful::class, function ($event) use (&$fail) {
            if ($fail) {
                $fail = false;
                throw new \RuntimeException('Queue unavailable before dispatch');
            }
            (new \App\Listeners\HandlePaymentSuccessful)->handle($event);
        });
        $this->signedWebhook($this->paidEvent($payment))->assertStatus(500);
        $this->assertTrue($dependent->fresh()->isPremium());
        Bus::assertNotDispatched(SendPaymentReceiptEmail::class);
        $this->signedWebhook($this->paidEvent($payment))->assertOk();
        $this->assertSame('2026-12-09 10:00:00', $payment->subscription->fresh()->ends_at->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(SendPaymentReceiptEmail::class, 1);
        Bus::assertDispatchedTimes(SendSubscriptionWelcomeEmail::class, 1);
    }

    public function test_printed_gift_receipt_keeps_original_recipient_term_and_expiry(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        [$guardian, $dependent, $plan] = $this->family();
        $first = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($first);
        $this->signedWebhook($this->paidEvent($first))->assertOk();
        $this->travelTo(Carbon::parse('2026-12-03 10:00:00'));
        $next = $this->prepareGift($guardian, $dependent, $plan);
        $this->attachSession($next);
        $this->signedWebhook($this->paidEvent($next))->assertOk();
        $plan->update(['name' => 'Changed catalogue name']);
        $this->actingAs($guardian)->get(route('payment.receipt', $first))->assertOk()
            ->assertSee('Purchaser')->assertSee('Gift Dependent')->assertSee('Gift Monthly')
            ->assertSee('One month')->assertSee('Dec 09, 2026')->assertSee('non-refundable');
    }

    private function paidApiResponse(Payment $payment): array
    {
        $session = $this->paidEvent($payment)['data']['attributes']['data'];
        $session['attributes']['status'] = 'active';
        $session['attributes']['livemode'] = false;

        return ['data' => $session];
    }

    private function paidEvent(Payment $payment): array
    {
        return ['data' => ['id' => 'evt_gift_'.$payment->id, 'attributes' => [
            'type' => 'checkout_session.payment.paid', 'livemode' => false,
            'data' => ['id' => 'cs_gift_'.$payment->id, 'type' => 'checkout_session', 'attributes' => [
                'metadata' => ['payment_scope' => 'subscription', 'payment_id' => (string) $payment->id,
                    'subscription_id' => (string) $payment->subscription_id, 'user_id' => (string) $payment->user_id],
                'payments' => [['id' => 'pay_gift_'.$payment->id, 'type' => 'payment', 'attributes' => [
                    'status' => 'paid', 'amount' => 12900, 'currency' => 'PHP', 'source' => ['type' => 'gcash'],
                ]]],
            ]],
        ]]];
    }

    private function signedWebhook(array $event): \Illuminate\Testing\TestResponse
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($event), 'gift_webhook_secret');

        return $this->postJson(route('webhook.paymongo'), $event, ['Paymongo-Signature' => 't='.$timestamp.',te='.$signature]);
    }

    private function prepareGift(User $guardian, User $dependent, SubscriptionPlan $plan): Payment
    {
        return app(GuardianSubscriptionGiftService::class)->prepare($guardian, $dependent, $plan, (string) Str::uuid());
    }

    private function checkoutUrl(Payment $payment): string
    {
        return route('payment.checkout.summary', ['subscription' => $payment->subscription_id, 'gift_payment' => $payment->id]);
    }

    private function attachSession(Payment $payment): void
    {
        $payment->update(['payment_details' => array_merge((array) $payment->payment_details, [
            'paymongo_checkout_session_id' => 'cs_gift_'.$payment->id,
        ])]);
    }

    private function purchaseUrl(User $dependent): string
    {
        return '/parent/children/'.$dependent->id.'/subscription-gift';
    }

    /** @return array{User, User, SubscriptionPlan, ParentChildAccount} */
    private function family(): array
    {
        DB::table('provinces')->insertOrIgnore(['code' => '402100000', 'name' => 'Test Province', 'region_code' => '040000000']);
        DB::table('cities')->insertOrIgnore(['code' => '402101000', 'name' => 'Test City', 'region_code' => '040000000', 'province_code' => '402100000', 'is_city' => true]);
        DB::table('barangays')->insertOrIgnore(['code' => '402101001', 'name' => 'Test Barangay', 'city_code' => '402101000']);
        $guardian = User::factory()->create([
            'name' => 'Gift Guardian', 'birthdate' => '1988-01-01', 'role' => 'learner', 'status' => 'active',
            'is_parent_registration' => true, 'parent_verification_status' => 'approved',
        ]);
        $dependent = User::factory()->create(['name' => 'Gift Dependent', 'birthdate' => '2010-01-01', 'role' => 'learner', 'status' => 'active']);
        foreach ([$guardian, $dependent] as $user) {
            $user->assignRole('learner');
            $user->learnerProfile()->create([
                'username' => 'gift_user_'.$user->id, 'birthdate' => $user->birthdate,
                'city_code' => '402101000', 'barangay_code' => '402101001',
                'gender' => 'female', 'is_parent_account' => $user->id === $guardian->id,
            ]);
        }
        $link = ParentChildAccount::create([
            'parent_user_id' => $guardian->id, 'child_user_id' => $dependent->id,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'verification_status' => 'approved', 'relationship_verified_at' => now(),
            'can_view_progress' => true, 'can_view_quiz_answers' => true,
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Gift Monthly', 'slug' => 'gift-monthly', 'price' => 129,
            'plan_audience' => 'learner', 'is_active' => true, 'features' => [], 'trial_days' => 0,
        ]);
        PlanPrice::create([
            'plan_id' => $plan->id, 'duration_mode' => 'preset', 'duration_unit' => 'month',
            'duration_count' => 1, 'duration_label' => 'One month', 'amount_minor' => 12900,
            'currency' => 'PHP', 'is_default' => true, 'is_active' => true,
        ]);

        return [$guardian, $dependent, $plan, $link];
    }
}
