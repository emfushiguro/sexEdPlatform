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
            $subscriptionEndsAt = $subscription?->ends_at ?? $subscription?->end_date;
        @endphp

        <div class="relative mb-8 overflow-hidden border shadow-sm rounded-2xl border-purple-200/60">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);"></div>
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="relative z-10 flex flex-col justify-between gap-6 p-6 md:flex-row md:items-center">
            <div class="flex items-start gap-4">
                <div>
                    <span class="sr-only">Subscription Status</span>
                    <h3 class="mb-1 text-lg font-bold text-white font-heading">Active Subscription</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($subscription)
                            <span class="text-sm text-purple-100">
                                Current Plan: <span class="font-semibold text-white">{{ $subscription->getPlanLabel() }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @if(in_array($summaryStatus, ['active', 'grace_period', 'scheduled_cancel']) && $subscription)
                <div class="flex flex-col gap-1 p-4 border md:text-right md:gap-0 bg-white/10 rounded-xl border-white/20 backdrop-blur-sm">
                    <span class="text-sm text-purple-100">Next Billing Statement</span>
                    @if($subscriptionEndsAt)
                        <span class="font-semibold text-white font-heading">
                            {{ $subscriptionEndsAt->format('F d, Y') }}
                            <span class="ml-1 text-sm font-normal text-purple-100">({{ $subscriptionEndsAt->diffForHumans() }})</span>
                        </span>
                    @endif
                </div>
            @endif
            </div>
        </div>
    @endif

<div class="relative z-10 flex flex-wrap items-center justify-between gap-4 mb-8">        
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-transparent sm:text-4xl bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500">Choose your subscription</h1>
            <p class="mt-2 text-sm text-gray-500">All active admin plans are shown below. Ineligible plans remain visible with explanation.</p>
        </div>
        <a href="{{ route('payment.history') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-purple-700 to-pink-500 hover:shadow-lg hover:shadow-purple-300/40 hover:-translate-y-0.5 transition-all duration-300">
            <i class="text-xs fi fi-rr-time-past"></i>
            Payment history
        </a>
    </div>

    @if(!empty($renewalNotice))
        @php
            $renewTone = ($renewalNotice['tone'] ?? 'expiring') === 'expired'
                ? ['border' => 'border-rose-200', 'bg' => 'bg-rose-50', 'text' => 'text-rose-700']
                : ['border' => 'border-amber-200', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700'];
            $renewEndsAt = $renewalNotice['ends_at'] ?? null;
        @endphp
        <div class="mb-8 rounded-2xl border {{ $renewTone['border'] }} {{ $renewTone['bg'] }} p-4 sm:p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-extrabold tracking-wide uppercase {{ $renewTone['text'] }}">{{ $renewalNotice['title'] }}</h2>
                    <p class="mt-1 text-sm text-gray-700">{{ $renewalNotice['message'] }}</p>
                    @if($renewEndsAt)
                        <p class="mt-1 text-xs text-gray-500">
                            Previous cycle ended on {{ $renewEndsAt->format('M d, Y h:i A') }}.
                        </p>
                    @endif
                </div>
                <form method="POST" action="{{ route('subscription.renew') }}" class="shrink-0">
                    @csrf
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 sm:w-auto">
                        Renew Subscription
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if(empty($planCards))
        <div class="p-10 text-center bg-white border border-gray-200 rounded-2xl">
            <h2 class="mb-2 text-xl font-semibold text-gray-900">No plans are currently available</h2>
            <p class="mb-6 text-sm text-gray-500">New plans may be published soon. You can continue using available free learning content in the meantime.</p>
            <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                Back to dashboard
            </a>
        </div>
    @else
        <div class="relative z-10 grid grid-cols-1 gap-6 mb-10 lg:grid-cols-3">
            <!-- Decorative background elements -->
            <div class="absolute inset-x-0 top-0 h-64 pointer-events-none bg-gradient-to-b from-purple-50/50 to-transparent -z-10 rounded-t-3xl blur-xl"></div>
            
            @foreach($planCards as $plan)
                <div class="relative group">
                    @if($plan['is_recommended'])
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-pink-400 to-purple-600 rounded-3xl blur opacity-30 group-hover:opacity-60 transition duration-500"></div>
                    @endif
                    <article class="relative h-full rounded-2xl border p-6 bg-white transition-all duration-300 group-hover:-translate-y-2 group-hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] {{ $plan['is_recommended'] ? 'border-purple-200 shadow-lg' : 'border-gray-200 shadow-sm' }} {{ !empty($plan['is_baseline']) ? 'border-purple-200 shadow-lg' : '' }} {{ !$plan['is_eligible'] && !$plan['is_current'] ? 'opacity-75' : '' }} flex flex-col">
<div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 transition-all duration-300 bg-clip-text group-hover:text-transparent group-hover:bg-gradient-to-r group-hover:from-purple-700 group-hover:to-pink-600">{{ $plan['name'] }}</h2>
                                @if(!empty($plan['description']))
                                <p class="mt-2 text-sm font-medium leading-relaxed text-gray-500">{{ $plan['description'] }}</p>
                            @endif
                            <p class="mt-2 text-xs text-gray-400">{{ (int) ($plan['visible_feature_count'] ?? count($plan['feature_labels'] ?? [])) }} visible features</p>
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

                    <div class="flex-grow pt-6 mt-6 border-t border-gray-100">
                        <ul class="space-y-3.5 text-sm text-gray-600">
                            @if(!empty($plan['includes_free_features']))
                                <li class="flex items-start gap-3 rounded-xl border border-purple-100 bg-purple-50/70 px-3 py-2 text-sm font-semibold text-purple-700">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-purple-700">+</span>
                                    <span class="leading-tight">All Free Plan Features</span>
                                </li>
                            @endif

                            @foreach(array_values($plan['feature_labels']) as $featureLabel)
                                <li class="flex items-start gap-3 transition-colors duration-200 group-hover:text-gray-900">
                                    <svg class="h-5 w-5 text-purple-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                                    <span class="leading-tight">{{ $featureLabel }}</span>
                                </li>
                            @endforeach

                            @if(empty($plan['feature_labels']) && !empty($plan['includes_free_features']))
                                <li class="text-xs font-medium text-gray-500">No additional premium features are configured yet.</li>
                            @endif
                        </ul>
                    </div>

                    <div class="flex flex-col justify-end flex-grow w-full pt-6 mt-auto">
                        @if(!empty($plan['is_baseline']))
                            <button type="button" disabled class="w-full py-3 text-sm font-bold text-white border shadow-md cursor-default rounded-xl bg-gradient-to-r from-purple-700 to-pink-500 ring-1 ring-purple-100/50">
                                Included by default
                            </button>
                        @elseif($plan['is_current'])
                            <button type="button" disabled class="w-full py-3 text-sm font-bold text-purple-700 border border-purple-200 cursor-default rounded-xl bg-purple-50 ring-1 ring-purple-100/50">
                                Current plan
                            </button>
                        @elseif(!$plan['is_eligible'])
                            <button type="button" disabled class="w-full py-3 text-sm font-bold text-gray-400 border border-gray-200 cursor-not-allowed rounded-xl bg-gray-50/50 backdrop-blur-sm">
                                Not available
                            </button>
                        @else
                            <button type="button" @click="openSummary({{ $plan['id'] }})" class="w-full py-3 rounded-xl text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300">
                                Continue
                            </button>
                        @endif
                        @if(!$plan['is_eligible'] && !$plan['is_current'] && !empty($plan['ineligible_reason']))
                            <p class="mt-2 text-xs text-center text-gray-500">{{ $plan['ineligible_reason'] }}</p>
                        @endif
                    </div>
                </article>
                </div>
            @endforeach
        </div>

        <div class="relative z-10 mb-24 overflow-hidden transition-all duration-300 border shadow-sm rounded-3xl border-gray-200/80 bg-white/60 backdrop-blur-xl lg:mb-8 hover:shadow-md">
            <button type="button" class="flex items-center justify-between w-full px-6 py-5 text-left transition-colors duration-300 hover:bg-purple-50/50 group" @click="toggleComparison()">
                <span>
                    <span class="block text-base font-extrabold text-gray-900 transition-colors group-hover:text-purple-700">Compare all plan features</span>
                    <span class="block mt-1 text-sm font-medium text-gray-500">Detailed matrix with Free baseline and premium plan differences</span>
                </span>
                <span class="flex items-center gap-2 text-sm font-bold text-purple-600 bg-purple-50 px-3 py-1.5 rounded-lg group-hover:bg-purple-100 transition-colors">
                    <span x-text="showComparison ? 'Hide' : 'Show'"></span>
                    <svg class="w-4 h-4 transition-transform duration-300 transform" :class="showComparison ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </span>
            </button>

            <div x-show="showComparison" x-collapse class="border-t border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-left text-gray-600">Feature</th>
                                @foreach($planCards as $plan)
                                    <th class="px-4 py-3 font-semibold text-center text-gray-600">
                                        <span>{{ $plan['name'] }}</span>
                                        @if(!empty($plan['is_baseline']))
                                            <span class="block mt-1 text-[10px] font-bold uppercase tracking-wider text-purple-600">Baseline</span>
                                        @elseif($plan['is_current'])
                                            <span class="block mt-1 text-[10px] font-bold uppercase tracking-wider text-purple-600">Current</span>
                                        @endif
                                    </th>
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
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700" aria-label="Included">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.414l-7.2 7.2a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-6 h-6 font-bold text-gray-400 bg-gray-100 rounded-full">-</span>
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
                    <p class="text-xs font-medium text-gray-500">Selected plan</p>
                    <p class="text-base font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500" x-text="selectedPlan ? selectedPlan.name : ''"></p>
                </div>
                <button type="button" @click="openSummary(selectedPlan.id)" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-500 hover:scale-105 active:scale-95 shadow-md shadow-purple-200 transition-all duration-300">Continue</button>
            </div>
        </div>

        <div x-show="showSummary" x-cloak class="fixed inset-0 z-50">
            <div class="absolute inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" @click="closeSummary()"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div class="relative flex items-end justify-center h-full p-4 sm:items-center">
                <div class="w-full max-w-md rounded-3xl bg-white border border-purple-100 shadow-[0_25px_50px_-12px_rgba(100,0,200,0.25)] p-6 sm:p-8 transform transition-all" @click.stop
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-95">
                    
                    <div class="absolute top-0 right-0 pt-5 pr-5">
                        <button type="button" @click="closeSummary()" class="p-2 text-gray-400 transition-colors rounded-full hover:text-gray-500 bg-gray-50 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <h3 class="pr-10 text-2xl font-extrabold text-gray-900">Confirm your plan</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">Review your selection before continuing to seamless payment.</p>

                    <div class="p-5 mt-6 space-y-3 border border-purple-100 shadow-inner rounded-2xl bg-gradient-to-b from-purple-50/50 to-white">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-500">Selected Plan</span>
                            <span class="font-extrabold text-purple-900" x-text="selectedPlan ? selectedPlan.name : ''"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm" x-show="selectedPrice">
                            <span class="font-medium text-gray-500">Billing Cycle</span>
                            <span class="font-bold text-gray-800" x-text="selectedPrice ? selectedPrice.label : ''"></span>
                        </div>
                        <div class="flex items-center justify-between pt-3 mt-3 border-t border-purple-100/60">
                            <span class="font-semibold text-gray-700">Total Amount</span>
                            <span class="text-lg font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-600" x-text="selectedPrice ? 'PHP ' + selectedPrice.amount_display : ''"></span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('subscription.subscribe') }}" class="space-y-3 mt-7" @submit="track('subscription_checkout_started')">
                        @csrf
                        <input type="hidden" name="plan_id" :value="selectedPlanId">
                        <p class="text-xs leading-relaxed text-gray-500">
                            By continuing, you confirm that subscription payments are final and non-refundable.
                        </p>
                        <button type="submit" class="w-full py-3.5 rounded-xl text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 hover:shadow-lg shadow-purple-200/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300">
                            Continue to payment process
                        </button>
                        <button type="button" @click="closeSummary()" class="w-full py-3 text-sm font-bold text-gray-600 transition-all duration-300 bg-white border border-gray-200 shadow-sm rounded-xl hover:text-gray-900 hover:border-gray-300 hover:bg-gray-50">
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


