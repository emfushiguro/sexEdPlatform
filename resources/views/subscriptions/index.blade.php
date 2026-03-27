@extends('layouts.learner-app')

@section('title', 'My Subscription')

@section('content')
<div class="max-w-6xl mx-auto" x-data="subscriptionPage({{ Js::from($planCards ?? []) }})" x-init="init()">
    @foreach(['success' => 'green', 'error' => 'red', 'info' => 'blue'] as $type => $color)
        @if(session($type))
            <div x-data="{ show: true }" x-init="
                Toastify({
                    text: '{{ session($type) }}',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    className: 'bg-{{ $color }}-500 rounded-xl font-medium'
                }).showToast();
            "></div>
        @endif
    @endforeach

    @if(($subscriptionSummary['has_subscription'] ?? false) && !empty($subscriptionSummary['status']))
        @php
            $summaryStatus = (string) $subscriptionSummary['status'];
            $summaryLabel = match($summaryStatus) {
                'scheduled_cancel' => 'Scheduled Cancel',
                'grace_period' => 'Grace Period',
                default => ucfirst(str_replace('_', ' ', $summaryStatus)),
            };
        @endphp

        <div class="mb-8 relative overflow-hidden rounded-2xl border border-purple-200/60 shadow-sm">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);"></div>
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="relative z-10 p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-start gap-4">
                <div>
                    <span class="sr-only">Subscription Status</span>
                    <h3 class="text-white font-bold font-heading text-lg mb-1">Active Subscription</h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($subscription)
                            <span class="text-purple-100 text-sm">
                                Current Plan: <span class="text-white font-semibold">{{ $subscription->getPlanLabel() }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @if(in_array($summaryStatus, ['active', 'grace_period', 'scheduled_cancel']) && $subscription)
                <div class="flex flex-col md:text-right gap-1 md:gap-0 bg-white/10 p-4 rounded-xl border border-white/20 backdrop-blur-sm">
                    <span class="text-purple-100 text-sm">Next Billing Statement</span>
                    <span class="text-white font-semibold font-heading">
                        {{ $subscription->end_date->format('F d, Y') }} 
                        <span class="text-purple-100 font-normal text-sm ml-1">({{ $subscription->end_date->diffForHumans() }})</span>
                    </span>
                </div>
            @endif
            </div>
        </div>
    @endif

<div class="mb-8 flex items-center justify-between gap-4 flex-wrap relative z-10">        
        <div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500">Choose your subscription</h1>
            <p class="text-sm text-gray-500 mt-2">All active admin plans are shown below. Ineligible plans remain visible with explanation.</p>
        </div>
        <a href="{{ route('payment.history') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 hover:shadow-lg hover:shadow-purple-300/40 hover:-translate-y-0.5 transition-all duration-300">
            <i class="fi fi-rr-time-past text-xs"></i>
            Payment history
        </a>
    </div>

    @if(empty($planCards))
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">No plans are currently available</h2>
            <p class="text-gray-500 text-sm mb-6">New plans may be published soon. You can continue using available free learning content in the meantime.</p>
            <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                Back to dashboard
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10 relative z-10">
            <!-- Decorative background elements -->
            <div class="absolute top-0 inset-x-0 h-64 bg-gradient-to-b from-purple-50/50 to-transparent -z-10 pointer-events-none rounded-t-3xl blur-xl"></div>
            
            @foreach($planCards as $plan)
                <div class="relative group">
                    @if($plan['is_recommended'])
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-pink-400 to-purple-600 rounded-3xl blur opacity-30 group-hover:opacity-60 transition duration-500"></div>
                    @endif
                    <article class="relative h-full rounded-2xl border p-6 bg-white transition-all duration-300 group-hover:-translate-y-2 group-hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] {{ $plan['is_recommended'] ? 'border-purple-200 shadow-lg' : 'border-gray-200 shadow-sm' }} {{ !$plan['is_eligible'] && !$plan['is_current'] ? 'opacity-75' : '' }} flex flex-col">
<div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 bg-clip-text group-hover:text-transparent group-hover:bg-gradient-to-r group-hover:from-purple-700 group-hover:to-pink-600 transition-all duration-300">{{ $plan['name'] }}</h2>
                                @if(!empty($plan['description']))
                                <p class="text-sm text-gray-500 mt-2 font-medium leading-relaxed">{{ $plan['description'] }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-2 shrink-0">
                            @if($plan['is_current'])
                                <span class="inline-flex items-center rounded-full bg-purple-100/80 px-3 py-1.5 text-xs font-bold text-purple-700 ring-1 ring-inset ring-purple-200/50 shadow-sm">Current</span>
                            @endif
                            @if($plan['is_recommended'])
                                <span class="inline-flex items-center rounded-full bg-gradient-to-r from-pink-500 to-purple-600 px-3 py-1.5 text-xs font-bold text-white shadow-md animate-pulse">Recommended</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 space-y-2 lg:min-h-[100px]">
                        @foreach($plan['prices'] as $price)
                            <button type="button"
                                class="w-full flex items-center justify-between rounded-xl border px-4 py-2.5 text-sm transition-all duration-300 transform"
                                :class="selectedPlanId === {{ $plan['id'] }} && selectedPriceId === {{ $price['id'] }} ? 'border-purple-400 bg-purple-50 text-purple-900 shadow-inner scale-[1.02] ring-1 ring-purple-100' : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-200 text-gray-700'"
                                @if(!$plan['is_eligible']) disabled @endif
                                @click="setSelection({{ $plan['id'] }}, {{ $price['id'] }})"
                            >
                                <span class="font-medium">{{ $price['label'] }}</span>
                                <span class="font-bold tracking-tight">PHP {{ $price['amount_display'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100 flex-grow">
                        <ul class="space-y-3.5 text-sm text-gray-600">
                            @foreach(array_slice(array_values($plan['feature_labels']), 0, 5) as $featureLabel)
                                <li class="flex items-start gap-3 transition-colors duration-200 group-hover:text-gray-900">
                                    <svg class="h-5 w-5 text-purple-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                                    <span class="leading-tight">{{ $featureLabel }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-auto pt-6 w-full flex-grow flex flex-col justify-end">
                        @if($plan['is_current'])
                            <button type="button" disabled class="w-full py-3 rounded-xl text-sm font-bold border border-purple-200 text-purple-700 bg-purple-50 cursor-default ring-1 ring-purple-100/50">
                                Current plan
                            </button>
                        @elseif(!$plan['is_eligible'])
                            <button type="button" disabled class="w-full py-3 rounded-xl text-sm font-bold border border-gray-200 text-gray-400 bg-gray-50/50 cursor-not-allowed backdrop-blur-sm">
                                Not available
                            </button>
                        @else
                            <button type="button" @click="openSummary({{ $plan['id'] }})" class="w-full py-3 rounded-xl text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300">
                                Continue
                            </button>
                        @endif
                    </div>
                </article>
                </div>
            @endforeach
        </div>

        <div class="rounded-3xl border border-gray-200/80 bg-white/60 backdrop-blur-xl overflow-hidden mb-24 lg:mb-8 shadow-sm hover:shadow-md transition-all duration-300 relative z-10">
            <button type="button" class="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-purple-50/50 transition-colors duration-300 group" @click="toggleComparison()">
                <span>
                    <span class="block text-base font-extrabold text-gray-900 group-hover:text-purple-700 transition-colors">Compare all plan features</span>
                    <span class="block text-sm text-gray-500 mt-1 font-medium">Detailed matrix based on active admin plans</span>
                </span>
                <span class="flex items-center gap-2 text-sm font-bold text-purple-600 bg-purple-50 px-3 py-1.5 rounded-lg group-hover:bg-purple-100 transition-colors">
                    <span x-text="showComparison ? 'Hide' : 'Show'"></span>
                    <svg class="w-4 h-4 transform transition-transform duration-300" :class="showComparison ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </span>
            </button>

            <div x-show="showComparison" x-collapse class="border-t border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold text-gray-600">Feature</th>
                                @foreach($planCards as $plan)
                                    <th class="px-4 py-3 text-center font-semibold text-gray-600">{{ $plan['name'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comparisonFeatures as $feature)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 text-gray-700">{{ $feature['label'] }}</td>
                                    @foreach($planCards as $plan)
                                        <td class="px-4 py-3 text-center">
                                            @if(in_array($feature['key'], $plan['feature_keys'], true))
                                                <span class="inline-flex w-6 h-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-label="Included">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.414l-7.2 7.2a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="inline-flex w-6 h-6 items-center justify-center rounded-full bg-gray-100 text-gray-400 font-bold">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="selectedPlan && selectedPlan.is_eligible && !showSummary" x-cloak class="lg:hidden fixed left-0 right-0 bottom-0 z-40 border-t border-purple-100 bg-white/90 backdrop-blur-lg px-5 py-4 shadow-[0_-10px_30px_rgba(200,100,250,0.1)] transition-all duration-300">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-gray-500 font-medium">Selected plan</p>
                    <p class="text-base font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500" x-text="selectedPlan ? selectedPlan.name : ''"></p>
                </div>
                <button type="button" @click="openSummary(selectedPlan.id)" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-500 hover:scale-105 active:scale-95 shadow-md shadow-purple-200 transition-all duration-300">Continue</button>
            </div>
        </div>

        <div x-show="showSummary" x-cloak class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeSummary()"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div class="relative h-full flex items-end sm:items-center justify-center p-4">
                <div class="w-full max-w-md rounded-3xl bg-white border border-purple-100 shadow-[0_25px_50px_-12px_rgba(100,0,200,0.25)] p-6 sm:p-8 transform transition-all" @click.stop
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-95">
                    
                    <div class="absolute top-0 right-0 pt-5 pr-5">
                        <button type="button" @click="closeSummary()" class="text-gray-400 hover:text-gray-500 bg-gray-50 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <h3 class="text-2xl font-extrabold text-gray-900 pr-10">Confirm your plan</h3>
                    <p class="text-sm text-gray-500 mt-2 font-medium">Review your selection before continuing to seamless payment.</p>

                    <div class="mt-6 rounded-2xl border border-purple-100 bg-gradient-to-b from-purple-50/50 to-white p-5 space-y-3 shadow-inner">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 font-medium">Selected Plan</span>
                            <span class="font-extrabold text-purple-900" x-text="selectedPlan ? selectedPlan.name : ''"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm" x-show="selectedPrice">
                            <span class="text-gray-500 font-medium">Billing Cycle</span>
                            <span class="font-bold text-gray-800" x-text="selectedPrice ? selectedPrice.label : ''"></span>
                        </div>
                        <div class="pt-3 mt-3 border-t border-purple-100/60 flex items-center justify-between">
                            <span class="text-gray-700 font-semibold">Total Amount</span>
                            <span class="text-lg font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-600" x-text="selectedPrice ? 'PHP ' + selectedPrice.amount_display : ''"></span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('subscription.subscribe') }}" class="mt-7 space-y-3" @submit="track('subscription_checkout_started')">
                        @csrf
                        <input type="hidden" name="plan_id" :value="selectedPlanId">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            By continuing, you confirm that subscription payments are final and non-refundable.
                        </p>
                        <button type="submit" class="w-full py-3.5 rounded-xl text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 hover:shadow-lg shadow-purple-200/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300">
                            Continue to payment process
                        </button>
                        <button type="button" @click="closeSummary()" class="w-full py-3 rounded-xl text-sm font-bold border border-gray-200 text-gray-600 hover:text-gray-900 hover:border-gray-300 hover:bg-gray-50 bg-white shadow-sm transition-all duration-300">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function subscriptionPage(planCards) {
    return {
        planCards: Array.isArray(planCards) ? planCards : [],
        selectedPlanId: null,
        selectedPriceId: null,
        showSummary: false,
        showComparison: false,

        track(eventName, payload = {}) {
            const detail = {
                event: eventName,
                source: 'subscription.index',
                viewport: window.innerWidth < 1024 ? 'mobile' : 'desktop',
                ...payload,
            };

            window.dispatchEvent(new CustomEvent('subscription-analytics', { detail }));

            if (Array.isArray(window.dataLayer)) {
                window.dataLayer.push(detail);
            }
        },

        get selectedPlan() {
            return this.planCards.find((plan) => plan.id === this.selectedPlanId) || null;
        },

        get selectedPrice() {
            if (!this.selectedPlan) return null;
            return (this.selectedPlan.prices || []).find((price) => price.id === this.selectedPriceId) || null;
        },

        init() {
            const initial = this.planCards.find((plan) => plan.is_recommended && plan.is_eligible)
                || this.planCards.find((plan) => plan.is_eligible && !plan.is_current)
                || null;

            this.track('subscription_plans_viewed', { plan_count: this.planCards.length });

            if (!initial) return;

            this.selectedPlanId = initial.id;
            const defaultPrice = (initial.prices || []).find((price) => price.is_default) || initial.prices?.[0] || null;
            this.selectedPriceId = defaultPrice ? defaultPrice.id : null;
        },

        setSelection(planId, priceId) {
            const plan = this.planCards.find((item) => item.id === planId);
            if (!plan || !plan.is_eligible) return;
            this.selectedPlanId = planId;
            this.selectedPriceId = priceId;
            this.track('subscription_plan_selected', {
                plan_id: planId,
                price_id: priceId,
            });
        },

        toggleComparison() {
            this.showComparison = !this.showComparison;
            if (this.showComparison) {
                this.track('subscription_compare_expanded');
            }
        },

        openSummary(planId) {
            const plan = this.planCards.find((item) => item.id === planId);
            if (!plan || !plan.is_eligible) return;

            this.selectedPlanId = planId;
            if (!this.selectedPriceId || !plan.prices.find((price) => price.id === this.selectedPriceId)) {
                const defaultPrice = plan.prices.find((price) => price.is_default) || plan.prices[0] || null;
                this.selectedPriceId = defaultPrice ? defaultPrice.id : null;
            }

            this.showSummary = true;
            this.track('subscription_continue_clicked', {
                plan_id: planId,
                price_id: this.selectedPriceId,
            });
        },

        closeSummary() {
            this.showSummary = false;
        }
    };
}
</script>
@endpush


