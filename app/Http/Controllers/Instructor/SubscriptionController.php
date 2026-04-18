<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\SubscribeInstructorPlanRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Instructor\InstructorPlanCapabilityService;
use App\Services\Instructor\InstructorSubscriptionPresentationService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
	public function __construct(
		private readonly SubscriptionService $subscriptionService,
		private readonly InstructorPlanCapabilityService $instructorPlanCapabilityService,
		private readonly InstructorSubscriptionPresentationService $instructorSubscriptionPresentationService,
	) 
	{
	}

	public function index(Request $request)
	{
		$user = $request->user();

		$this->subscriptionService->reconcileLifecycleForUser($user);

		$currentSubscription = $this->resolveCurrentInstructorSubscription($user);
		$baselineSnapshot = $this->instructorPlanCapabilityService->getBaselineSnapshot();

		$availablePlans = SubscriptionPlan::query()
			->active()
			->where('plan_audience', 'instructor')
			->with(['planPrices', 'featureEntitlements.feature'])
			->ordered()
			->get();

		$pagePayload = $this->instructorSubscriptionPresentationService
			->buildPagePayload($availablePlans, $baselineSnapshot, $currentSubscription);

		$baselinePlanCard = $pagePayload['baseline_plan_card'];
		$paidPlanCards = $pagePayload['paid_plan_cards'];
		$comparisonRows = $pagePayload['comparison_rows'];

		$currentPlanDisplayName = 'Free Plan';
		$pendingPayment = null;
		if ($currentSubscription) {
			$currentSubscription->loadMissing('plan');
			$resolvedPlan = $currentSubscription->getRelation('plan');
			if ($resolvedPlan instanceof SubscriptionPlan) {
				$currentPlanDisplayName = (string) $resolvedPlan->name;
			}

			$currentStatus = strtolower((string) ($currentSubscription->status->value ?? $currentSubscription->status));
			if ($currentStatus === 'pending') {
				$pendingPayment = $currentSubscription->payments()
					->whereIn('status', ['pending', 'processing'])
					->latest('id')
					->first();
			}
		}

		return view('instructor.subscriptions.index', compact(
			'currentSubscription',
			'currentPlanDisplayName',
			'pendingPayment',
			'baselinePlanCard',
			'paidPlanCards',
			'comparisonRows',
		));
	}

	public function subscribe(SubscribeInstructorPlanRequest $request)
	{
		/** @var User $user */
		$user = $request->user();

		if ($this->hasActiveInstructorPremiumSubscription($user)) {
			return redirect()->route('instructor.subscriptions.index')
				->with('error', 'You already have an active instructor subscription.');
		}

		$plan = SubscriptionPlan::query()
			->active()
			->where('plan_audience', 'instructor')
			->findOrFail((int) $request->input('plan_id'));

		if (!$this->isPlanPaidForCheckout($plan)) {
			return redirect()->route('instructor.subscriptions.index')
				->with('error', 'The selected plan does not require checkout. Please choose a paid instructor plan.');
		}

		try {
			$subscription = $this->subscriptionService->create($user, $plan);
		} catch (\Exception $exception) {
			return redirect()->route('instructor.subscriptions.index')
				->with('error', $exception->getMessage());
		}

		/** @var \App\Models\Payment|null $payment */
		$payment = $subscription->payments()->latest('id')->first();
		if ($payment) {
			$payment->update([
				'payment_details' => array_merge((array) $payment->payment_details, [
					'payment_scope' => 'subscription',
					'role_context' => 'instructor',
					'plan_id' => $plan->id,
					'plan_name' => $plan->name,
					'created_via' => 'instructor_subscription_controller',
				]),
			]);
		}

		return redirect()->route('instructor.payments.checkout.summary', $subscription)
			->with('success', 'Review your instructor checkout details to continue with secure payment.');
	}

	private function resolveCurrentInstructorSubscription(User $user): ?Subscription
	{
		$eligibleSubscription = $this->subscriptionService->getEligibleSubscriptionForEntitlements($user);

		if ($eligibleSubscription && $eligibleSubscription->plan_id) {
			$eligibleSubscription->loadMissing('plan');
			if ((string) ($eligibleSubscription->plan?->plan_audience ?? '') === 'instructor') {
				return $eligibleSubscription;
			}
		}

		$latestInstructorSubscription = $user->subscriptions()
			->whereHas('plan', function ($query) {
				$query->where('plan_audience', 'instructor');
			})
			->with('plan')
			->latest('id')
			->first();

		return $latestInstructorSubscription ?: null;
	}

	private function hasActiveInstructorPremiumSubscription(User $user): bool
	{
		$currentSubscription = $this->resolveCurrentInstructorSubscription($user);
 		if (!$currentSubscription) {
			return false;
		}

		$status = (string) ($currentSubscription->status->value ?? $currentSubscription->status);
		$isActiveWindow = in_array($status, ['active', 'grace_period'], true);
		$currentSubscription->loadMissing('plan');
		$resolvedPlan = $currentSubscription->getRelation('plan');
		$isPaidPlan = $resolvedPlan instanceof SubscriptionPlan
			? $this->isPlanPaidForCheckout($resolvedPlan)
			: false;

		return $isActiveWindow && $isPaidPlan;
	}

	private function isPlanPaidForCheckout(SubscriptionPlan $plan): bool
	{
		$plan->loadMissing('planPrices');

		$hasPaidActivePriceRow = $plan->planPrices
			->contains(fn ($price) => (bool) ($price->is_active ?? false) && (int) ($price->amount_minor ?? 0) > 0);

		if ($hasPaidActivePriceRow) {
			return true;
		}

		return (float) ($plan->price ?? 0) > 0;
	}
}
