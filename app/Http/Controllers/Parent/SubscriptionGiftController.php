<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\StoreSubscriptionGiftRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\GuardianSubscriptionGiftService;
use App\Services\LearnerSubscriptionPlanCatalog;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionGiftController extends Controller
{
    public function create(User $child, LearnerSubscriptionPlanCatalog $catalog, SubscriptionService $subscriptions, GuardianSubscriptionGiftService $gifts): View
    {
        $this->authorize('giftSubscription', $child);
        $subscriptions->reconcileLifecycleForUser($child);
        $currentSubscriptions = $gifts->currentSubscriptions($child);
        $currentSubscription = $currentSubscriptions->first();
        $currentPlan = $currentSubscription?->getRelation('plan');
        $renewalStatus = $currentSubscription ? $subscriptions->getRenewalStatus($currentSubscription) : null;
        $canRenew = $currentSubscription && $gifts->canRenewSubscription($currentSubscription, $currentSubscriptions) && $currentPlan
            && $currentPlan->is_active && ! $currentPlan->isArchived()
            && $catalog->isAvailable($currentPlan) && $catalog->effectivePrice($currentPlan) !== null;
        $planCards = $catalog->giftCards($currentSubscription);
        $comparisonPlanCards = $catalog->prependFreeBaselineCard($planCards, $currentSubscription);

        return view('parent.subscriptions.gift', [
            'child' => $child,
            'planCards' => $planCards,
            'comparisonPlanCards' => $comparisonPlanCards,
            'comparisonFeatures' => $catalog->comparisonFeatures($comparisonPlanCards),
            'currentSubscription' => $currentSubscription,
            'currentPlanName' => $currentPlan?->name ?? $currentSubscription?->getPlanLabel(),
            'renewalStatus' => $renewalStatus,
            'canRenew' => $canRenew,
            'hasEligiblePlans' => collect($planCards)->contains(fn ($card) => $card['is_eligible']),
            'requestKey' => (string) Str::uuid(),
        ]);
    }

    public function store(StoreSubscriptionGiftRequest $request, User $child, GuardianSubscriptionGiftService $gifts): RedirectResponse
    {
        $payment = $gifts->prepare($request->user(), $child,
            SubscriptionPlan::findOrFail($request->integer('plan_id')), $request->validated('request_key'));

        return redirect()->route('payment.checkout.summary', [
            'subscription' => $payment->subscription_id, 'gift_payment' => $payment->id,
        ]);
    }
}
