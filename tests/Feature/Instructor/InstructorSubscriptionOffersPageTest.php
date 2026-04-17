<?php

namespace Tests\Feature\Instructor;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\FeatureCatalog;
use App\Models\Payment;
use App\Models\PlanFeatureEntitlement;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\SubscriptionFeatureKeys;
use Tests\TestCase;

class InstructorSubscriptionOffersPageTest extends TestCase
{
	public function test_instructor_subscription_page_lists_only_instructor_plans(): void
	{
		$instructor = $this->createInstructorUser();

		SubscriptionPlan::query()->create([
			'name' => 'Instructor Starter',
			'slug' => 'instructor-starter-' . uniqid(),
			'description' => 'Instructor plan',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		SubscriptionPlan::query()->create([
			'name' => 'Learner Premium',
			'slug' => 'learner-premium-' . uniqid(),
			'description' => 'Learner plan',
			'price' => 199,
			'features' => [],
			'plan_audience' => 'learner',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		$this->actingAs($instructor)
			->get(route('instructor.subscriptions.index'))
			->assertOk()
			->assertSeeText('Upgrade your instructor tools')
			->assertSeeText('Instructor Starter')
			->assertDontSeeText('Learner Premium');
	}

	public function test_instructor_subscription_page_shows_dynamic_labels_and_comparison_matrix(): void
	{
		$instructor = $this->createInstructorUser();

		$freePlan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Free Baseline',
			'slug' => 'instructor-free-baseline-' . uniqid(),
			'description' => 'Default baseline',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		$premiumPlan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Premium',
			'slug' => 'instructor-premium-dynamic-' . uniqid(),
			'description' => 'Premium plan',
			'price' => 399.99,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 2,
		]);

		$this->attachEntitlement($freePlan, SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT, 'Published Modules Limit', 'quota', 3, true, false, 'modules');
		$this->attachEntitlement($freePlan, SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE, 'Free Module Learner Cap', 'quota', 100, true, false, 'learners');
		$this->attachEntitlement($freePlan, SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE, 'Paid Module Learner Cap', 'quota', 100, true, false, 'learners');
		$this->attachEntitlement($freePlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES, 'Can Publish Paid Modules', 'boolean', null, false, false);
		$this->attachEntitlement($freePlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS, 'Can Receive Paid Enrollments', 'boolean', null, false, false);
		$this->attachEntitlement($freePlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS, 'Can View Earnings', 'boolean', null, false, false);

		$this->attachEntitlement($premiumPlan, SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT, 'Published Modules Limit', 'quota', 10, true, false, 'modules');
		$this->attachEntitlement($premiumPlan, SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE, 'Free Module Learner Cap', 'quota', 500, true, false, 'learners');
		$this->attachEntitlement($premiumPlan, SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE, 'Paid Module Learner Cap', 'quota', 500, true, false, 'learners');
		$this->attachEntitlement($premiumPlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES, 'Can Publish Paid Modules', 'boolean', null, true, true);
		$this->attachEntitlement($premiumPlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS, 'Can Receive Paid Enrollments', 'boolean', null, true, true);
		$this->attachEntitlement($premiumPlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS, 'Can View Earnings', 'boolean', null, true, true);

		$this->actingAs($instructor)
			->get(route('instructor.subscriptions.index'))
			->assertOk()
			->assertSeeText('Publish up to 3 modules')
			->assertSeeText('Publish up to 10 modules')
			->assertSeeText('Compare all plan features')
			->assertSee('data-testid="instructor-feature-comparison-matrix"', false);
	}

	public function test_instructor_sidebar_contains_subscriptions_link(): void
	{
		$instructor = $this->createInstructorUser();

		$this->actingAs($instructor)
			->get(route('instructor.modules.index'))
			->assertOk()
			->assertSee(route('instructor.subscriptions.index'), false)
			->assertSeeText('Subscriptions');
	}

	public function test_instructor_paid_plan_subscribe_redirects_to_instructor_checkout(): void
	{
		$instructor = $this->createInstructorUser();

		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Premium',
			'slug' => 'instructor-premium-' . uniqid(),
			'description' => 'Instructor premium plan',
			'price' => 199.99,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 10,
		]);

		$response = $this->actingAs($instructor)
			->post(route('instructor.subscriptions.subscribe'), [
				'plan_id' => $plan->id,
			]);

		$subscription = Subscription::query()
			->where('user_id', $instructor->id)
			->where('plan_id', $plan->id)
			->latest('id')
			->first();

		$this->assertNotNull($subscription);
		$this->assertSame(SubscriptionStatus::Pending->value, (string) ($subscription->status->value ?? $subscription->status));

		$response->assertRedirect(route('instructor.payments.checkout.summary', $subscription));

		$this->assertDatabaseHas('payments', [
			'user_id' => $instructor->id,
			'subscription_id' => $subscription->id,
			'status' => PaymentStatus::Pending->value,
		]);
	}

	public function test_pending_instructor_subscription_shows_resume_checkout_instead_of_current_plan_state(): void
	{
		$instructor = $this->createInstructorUser();

		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Pending Plan',
			'slug' => 'instructor-pending-' . uniqid(),
			'description' => 'Pending checkout plan',
			'price' => 189.99,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 12,
		]);

		$subscription = Subscription::query()->create([
			'user_id' => $instructor->id,
			'plan_id' => $plan->id,
			'plan' => $plan->slug,
			'status' => SubscriptionStatus::Pending,
			'start_date' => now(),
			'end_date' => now()->addMonth(),
			'starts_at' => now(),
			'ends_at' => now()->addMonth(),
			'price_paid' => 189.99,
			'auto_renew' => true,
		]);

		$payment = Payment::query()->create([
			'user_id' => $instructor->id,
			'subscription_id' => $subscription->id,
			'amount' => 189.99,
			'method' => 'paymongo',
			'status' => PaymentStatus::Pending,
			'transaction_id' => 'TXN-PENDING-' . strtoupper(uniqid()),
			'payment_details' => [
				'payment_scope' => 'subscription',
				'role_context' => 'instructor',
			],
		]);

		$this->actingAs($instructor)
			->get(route('instructor.subscriptions.index'))
			->assertOk()
			->assertSeeText('Pending Payment')
			->assertSeeText('Resume pending checkout')
			->assertSee(route('instructor.payments.pending', $payment), false);
	}

	public function test_instructor_paid_plan_with_zero_legacy_price_and_paid_price_row_is_checkout_eligible(): void
	{
		$instructor = $this->createInstructorUser();

		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Premium Normalized',
			'slug' => 'instructor-premium-normalized-' . uniqid(),
			'description' => 'Paid via plan prices',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 11,
		]);

		PlanPrice::query()->create([
			'plan_id' => $plan->id,
			'duration_mode' => 'preset',
			'duration_unit' => 'month',
			'duration_count' => 1,
			'duration_label' => 'Monthly',
			'amount_minor' => 25900,
			'currency' => 'PHP',
			'is_default' => true,
			'is_active' => true,
		]);

		$response = $this->actingAs($instructor)
			->post(route('instructor.subscriptions.subscribe'), [
				'plan_id' => $plan->id,
			]);

		$subscription = Subscription::query()
			->where('user_id', $instructor->id)
			->where('plan_id', $plan->id)
			->latest('id')
			->first();

		$this->assertNotNull($subscription);
		$response->assertRedirect(route('instructor.payments.checkout.summary', $subscription));
	}

	public function test_instructor_payment_history_page_renders_subscription_transactions(): void
	{
		$instructor = $this->createInstructorUser();

		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Premium',
			'slug' => 'instructor-premium-history-' . uniqid(),
			'description' => 'Instructor premium plan',
			'price' => 299.99,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 20,
		]);

		$subscription = Subscription::query()->create([
			'user_id' => $instructor->id,
			'plan_id' => $plan->id,
			'plan' => $plan->slug,
			'status' => SubscriptionStatus::Active,
			'start_date' => now(),
			'end_date' => now()->addMonth(),
			'starts_at' => now(),
			'ends_at' => now()->addMonth(),
			'price_paid' => 299.99,
			'auto_renew' => true,
		]);

		$payment = Payment::query()->create([
			'user_id' => $instructor->id,
			'subscription_id' => $subscription->id,
			'amount' => 299.99,
			'method' => 'paymongo',
			'status' => PaymentStatus::Completed,
			'transaction_id' => 'TXN-HISTORY-' . strtoupper(uniqid()),
			'payment_details' => [
				'payment_scope' => 'subscription',
				'role_context' => 'instructor',
				'plan_name' => $plan->name,
			],
			'paid_at' => now(),
		]);

		$this->actingAs($instructor)
			->get(route('instructor.payments.history'))
			->assertOk()
			->assertSeeText('Subscription Payment History')
			->assertSeeText($plan->name)
			->assertSeeText($payment->transaction_id);
	}

	private function createInstructorUser(): User
	{
		$instructor = User::factory()->create([
			'role' => 'instructor',
		]);
		$instructor->assignRole('instructor');

		return $instructor;
	}

	private function attachEntitlement(
		SubscriptionPlan $plan,
		string $key,
		string $name,
		string $valueType,
		?int $quotaValue = null,
		bool $enabled = true,
		bool $unlimited = false,
		?string $unitLabel = null,
	): void {
		$feature = FeatureCatalog::query()->updateOrCreate(
			['key' => $key],
			[
				'name' => $name,
				'description' => $name,
				'value_type' => $valueType,
				'unit_label' => $unitLabel,
				'category' => 'instructor',
				'is_active' => true,
			]
		);

		PlanFeatureEntitlement::query()->updateOrCreate(
			[
				'plan_id' => $plan->id,
				'feature_id' => $feature->id,
			],
			[
				'is_enabled' => $enabled,
				'quota_value' => $quotaValue,
				'is_unlimited' => $unlimited,
			]
		);
	}
}
