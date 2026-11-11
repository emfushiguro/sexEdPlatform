@extends('layouts.learner-app')

@section('title', 'Gift Subscription')

@section('content')
<div class="max-w-6xl mx-auto" x-data="giftSubscriptionPage(@js($planCards))">
    <a href="{{ route('parent.children.index') }}" class="mb-6 inline-flex text-sm font-semibold text-purple-700 hover:underline">Back to My Dependents</a>

    @if($currentSubscription)
        <div class="relative mb-8 overflow-hidden rounded-2xl border border-purple-200/60 shadow-sm">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);"></div>
            <div class="relative flex flex-col justify-between gap-6 p-6 text-white sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-bold">{{ ucfirst(str_replace('_', ' ', $currentSubscription->status->value)) }} Subscription</h2>
                    <p class="mt-1 text-sm text-purple-100">Current Plan: <span class="font-semibold text-white">{{ $currentPlanName }}</span></p>
                </div>
                @if($renewalStatus['effective_end_at'] ?? null)
                    <div class="rounded-xl border border-white/20 bg-white/10 p-4 sm:text-right">
                        <p class="text-sm text-purple-100">Paid access expires</p>
                        <p class="font-semibold">{{ $renewalStatus['effective_end_at']->format('F d, Y') }} <span class="text-sm font-normal text-purple-100">({{ $renewalStatus['effective_end_at']->diffForHumans() }})</span></p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-3xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500 sm:text-4xl">Choose a subscription for {{ $child->full_name ?: $child->name }}</h1>
        <a href="{{ route('payment.history') }}" class="rounded-xl bg-gradient-to-r from-purple-700 to-pink-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:shadow-lg">Payment history</a>
    </div>

    @if($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @if($canRenew)
        <div class="mb-8 flex flex-col justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm sm:flex-row sm:items-center">
            <div>
                <h2 class="text-sm font-extrabold uppercase tracking-wide text-amber-700">Your dependent's premium access is expiring soon</h2>
                <p class="mt-1 text-sm text-gray-700">Renew early to keep the remaining paid time and extend access.</p>
                <p class="mt-1 text-xs text-gray-500">Current cycle ends on {{ $renewalStatus['effective_end_at']->format('M d, Y h:i A') }}.</p>
            </div>
            <form method="POST" action="{{ route('parent.children.subscription-gift.store', $child) }}" @submit="submitting = true" class="shrink-0">
                @csrf
                <input type="hidden" name="request_key" value="{{ $requestKey }}">
                <input type="hidden" name="plan_id" value="{{ $currentSubscription->plan_id }}">
                <button type="submit" :disabled="submitting" class="w-full rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 disabled:opacity-50">Renew via Checkout</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('parent.children.subscription-gift.store', $child) }}" @submit="submitting = true">
        @csrf
        <input type="hidden" name="request_key" value="{{ old('request_key', $requestKey) }}">
        <input type="hidden" name="plan_id" :value="selectedPlanId || ''">

        <div class="relative z-10 grid grid-cols-1 gap-6 mb-10 lg:grid-cols-3">
            @foreach($planCards as $plan)
                <article class="relative flex h-full flex-col rounded-2xl border bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg {{ $plan['is_recommended'] ? 'border-purple-300 shadow-lg' : 'border-gray-200' }} {{ !$plan['is_eligible'] && !$plan['is_current'] ? 'opacity-75' : '' }}">
                    @if($plan['is_recommended'])
                        <div class="absolute -top-3 left-6 rounded-full bg-gradient-to-r from-pink-500 to-purple-600 px-3 py-1.5 text-xs font-bold text-white shadow-md">Recommended</div>
                    @endif
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">{{ $plan['name'] }}</h2>
                            @if($plan['description'])
                                <p class="mt-2 text-sm font-medium leading-relaxed text-gray-500">{{ $plan['description'] }}</p>
                            @endif
                        </div>
                        @if($plan['is_current'])
                            <span class="shrink-0 rounded-full bg-purple-100 px-3 py-1.5 text-xs font-bold text-purple-700">Current</span>
                        @elseif($plan['is_free'] && !$currentSubscription)
                            <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-600">Included</span>
                        @endif
                    </div>

                    <div class="mt-6 min-h-[72px]">
                        @if($plan['default_price'])
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700">
                                <span class="font-medium">{{ $plan['default_price']['label'] }}</span>
                                <span class="font-bold">{{ $plan['default_price']['currency'] }} {{ $plan['default_price']['amount_display'] }}</span>
                            </div>
                        @else
                            <p class="text-sm font-semibold text-gray-500">No paid checkout required</p>
                        @endif
                    </div>

                    <div class="flex-grow border-t border-gray-100 pt-6 mt-6">
                        <ul class="space-y-3 text-sm text-gray-600">
                            @forelse($plan['feature_labels'] as $featureLabel)
                                <li class="flex items-start gap-3">
                                    <svg class="h-5 w-5 shrink-0 text-purple-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12" /></svg>
                                    <span>{{ $featureLabel }}</span>
                                </li>
                            @empty
                                <li class="text-sm text-gray-400">No additional features configured.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="pt-6 mt-auto">
                        @if($plan['is_current'])
                            <button type="button" disabled class="w-full cursor-default rounded-xl border border-purple-200 bg-purple-50 py-3 text-sm font-bold text-purple-700">Current plan</button>
                        @elseif($plan['is_eligible'])
                            <button type="button" @click="selectPlan({{ $plan['id'] }})" :class="selectedPlanId === {{ $plan['id'] }} ? 'ring-2 ring-purple-300 bg-brand-600' : 'bg-brand-500'" class="w-full rounded-xl py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-600">
                                <span x-text="selectedPlanId === {{ $plan['id'] }} ? 'Selected' : 'Gift This Plan'"></span>
                            </button>
                        @elseif($plan['is_free'] && !$currentSubscription)
                            <button type="button" disabled class="w-full cursor-default rounded-xl border border-purple-200 bg-purple-50 py-3 text-sm font-bold text-purple-700">Included by default</button>
                        @else
                            <button type="button" disabled class="w-full cursor-not-allowed rounded-xl border border-gray-200 bg-gray-50 py-3 text-sm font-bold text-gray-400">Not available</button>
                            @if($plan['ineligible_reason'])
                                <p class="mt-2 text-center text-xs text-gray-500">{{ $plan['ineligible_reason'] }}</p>
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if($comparisonFeatures)
            <details class="group relative z-10 mb-8 overflow-hidden rounded-3xl border border-gray-200/80 bg-white/60 shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5">
                    <span>
                        <span class="block text-base font-extrabold text-gray-900">Compare all plan features</span>
                        <span class="mt-1 block text-sm font-medium text-gray-500">Detailed matrix with Free baseline and premium plan differences</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2 rounded-lg bg-purple-50 px-3 py-1.5 text-sm font-bold text-purple-600">
                        <span class="group-open:hidden">Show</span><span class="hidden group-open:inline">Hide</span>
                        <svg class="h-4 w-4 transition-transform duration-300 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </span>
                </summary>
                <div class="border-t border-gray-100">
                    @include('subscriptions.partials.plan-comparison-table', ['planCards' => $comparisonPlanCards])
                </div>
            </details>
        @endif

        @if($hasEligiblePlans)
        <div class="sticky bottom-4 z-20 mx-auto max-w-xl rounded-2xl border border-purple-200 bg-white p-4 shadow-xl">
            <p class="text-center text-xs text-gray-500">Gift payments are final and non-refundable under the Terms and Agreement.</p>
            <button type="submit" :disabled="!selectedPlanId || submitting" class="mt-3 w-full rounded-xl bg-brand-500 py-3.5 text-sm font-bold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                <span x-show="!submitting">Continue to Gift Checkout</span><span x-show="submitting" x-cloak>Preparing checkout…</span>
            </button>
        </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
function giftSubscriptionPage(planCards) {
    const recommended = (planCards || []).find((plan) => plan.is_recommended && plan.is_eligible);
    return {
        selectedPlanId: recommended ? recommended.id : null,
        submitting: false,
        selectPlan(planId) { this.selectedPlanId = planId; },
    };
}
</script>
@endpush

