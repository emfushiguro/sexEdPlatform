@extends('layouts.admin')

@section('title', 'Subscription Plans')
@section('page-title', 'Subscription Plans')

@section('content')
    <div x-data="subscriptionPlansPage(@js($plans->items()), '{{ route('admin.subscription-plans.store') }}')"
         x-init="init()"
         @keydown.escape.window="if (showCreatePlanModal) { closeCreatePlanModal(); }">
    <span class="hidden" data-testid="plan-wizard-mode" x-text="wizardMode"></span>
        <span class="hidden" data-testid="plan-wizard-step-1"></span>
        <span class="hidden" data-testid="plan-wizard-step-2"></span>
        <span class="hidden" data-testid="plan-wizard-step-3"></span>
    <span class="hidden">{{ route('admin.subscribers.store-plan') }}</span>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6 gap-3">
        <div>
             <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Subscription Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage subscriber lifecycle, plan pricing, and entitlements.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.subscription-plans.archived') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                Archived Plans
                <span class="inline-flex min-w-5 justify-center rounded-full bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-200">
                    {{ $stats['archived'] ?? 0 }}
                </span>
            </a>

            <button type="button"
               data-testid="open-create-plan-modal"
                  @click="openCreatePlanModal()"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors shadow-theme-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Plan
            </button>
        </div>
    </div>

    {{-- Create/Edit Plan Wizard Modal --}}
    <div x-show="showCreatePlanModal"
         x-cloak
         data-testid="create-plan-fullscreen-modal"
         data-sidebar-lock-hook="create-plan-modal"
         class="fixed inset-0 z-[100000] flex items-center justify-center p-4 sm:p-6 lg:p-8"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">

        <!-- Backdrop -->
        <div x-show="showCreatePlanModal"
             x-transition.opacity
             class="fixed inset-0 bg-gray-900/65 backdrop-blur-md transition-opacity"
             @click="closeCreatePlanModal()"></div>

        <!-- Modal Panel -->
        <div x-show="showCreatePlanModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 shadow-2xl transition-all w-full max-w-4xl max-h-[90vh] flex flex-col">

            {{-- Modal Header with Stepper --}}
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" id="modal-title" x-text="wizardMode === 'edit' ? 'Edit Plan' : 'Create New Subscription Plan'"></h2>
                    <button type="button" @click="closeCreatePlanModal()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-500 dark:hover:text-gray-300 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Reactive Stepper Visual --}}
                <div class="max-w-xl mx-auto">
                    <div class="flex items-center justify-between relative">
                        <template x-for="s in [1,2,3,4]" :key="s">
                            <div class="flex flex-col items-center relative z-10 w-24 cursor-pointer"
                                 :data-testid="s <= 3 ? 'plan-wizard-step-' + s : null"
                                 @click="currentStep > s ? currentStep = s : null">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 shadow-sm"
                                     :class="{
                                         'bg-gradient-to-br from-purple-600 to-indigo-700 text-white ring-4 ring-purple-100 dark:ring-purple-900/50': currentStep === s,
                                         'bg-purple-600 text-white': currentStep > s,
                                         'bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 text-gray-400': currentStep < s
                                     }">
                                    <template x-if="currentStep > s">
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    </template>
                                    <template x-if="currentStep <= s">
                                        <span class="text-sm font-bold" x-text="s"></span>
                                    </template>
                                </div>
                                <span class="mt-2 text-xs font-semibold uppercase tracking-wider text-center"
                                      :class="currentStep >= s ? 'text-purple-700 dark:text-purple-400' : 'text-gray-400 dark:text-gray-600'"
                                      x-text="s === 1 ? 'Basics' : (s === 2 ? 'Billing' : (s === 3 ? 'Features' : 'Review'))"></span>
                            </div>
                        </template>
                        <!-- Connecting Lines -->
                        <div class="absolute top-5 left-12 right-12 h-0.5 bg-gray-200 dark:bg-gray-700 -z-10">
                            <div class="h-full bg-purple-600 transition-all duration-500 ease-in-out"
                                 :style="'width: ' + ((currentStep - 1) / 3 * 100) + '%'"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Body --}}
            <form id="planWizardForm" method="POST" :action="wizardAction()" class="flex flex-col h-full overflow-hidden">
                @csrf
                <template x-if="wizardMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 overflow-y-auto flex-1 bg-white dark:bg-gray-900">
                    {{-- STEP 1: Plan Basics + Audience --}}
                    <div x-show="currentStep === 1" x-transition.opacity class="space-y-6 max-w-2xl mx-auto">
                        <div class="mb-2">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-2">Plan Basics</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Define the core identity and target audience for this plan.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Plan Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required
                                   placeholder="e.g., Premium Learner Plan"
                                   class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all"/>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                            <textarea name="description" x-model="form.description" rows="3"
                                      placeholder="Describe what makes this plan special for your audience..."
                                      class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 resize-none transition-all"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Plan For <span class="text-red-500">*</span></label>
                            <select name="plan_audience" x-model="form.plan_audience" required
                                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all">
                                <option value="">-- Select Target Audience --</option>
                                <option value="learner">Learner Plan</option>
                                <option value="instructor">Instructor Plan</option>
                                <option value="connectors">Connectors Plan (Organizations)</option>
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Choose who this plan is designed for</p>
                        </div>
                    </div>

                    {{-- STEP 2: Billing Mode + Pricing --}}
                    <div x-show="currentStep === 2" x-transition.opacity class="space-y-6 max-w-3xl mx-auto" x-cloak>
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-2">Billing Configuration</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Choose billing frequency and set pricing.</p>
                        </div>

                        {{-- Billing Mode Selection --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Billing Mode <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="form.billing_mode === 'monthly' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700'">
                                    <input type="radio" name="billing_mode" value="monthly" x-model="form.billing_mode" class="sr-only" required>
                                    <div class="flex flex-col flex-1">
                                        <span class="text-sm font-semibold" :class="form.billing_mode === 'monthly' ? 'text-purple-700 dark:text-purple-400' : 'text-gray-700 dark:text-gray-300'">Monthly</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Billed every month</span>
                                    </div>
                                    <svg x-show="form.billing_mode === 'monthly'" class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </label>

                                <label class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="form.billing_mode === 'annual' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700'">
                                    <input type="radio" name="billing_mode" value="annual" x-model="form.billing_mode" class="sr-only">
                                    <div class="flex flex-col flex-1">
                                        <span class="text-sm font-semibold" :class="form.billing_mode === 'annual' ? 'text-purple-700 dark:text-purple-400' : 'text-gray-700 dark:text-gray-300'">Annual</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Billed every year</span>
                                    </div>
                                    <svg x-show="form.billing_mode === 'annual'" class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </label>

                                <label class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="form.billing_mode === 'custom' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700'">
                                    <input type="radio" name="billing_mode" value="custom" x-model="form.billing_mode" class="sr-only">
                                    <div class="flex flex-col flex-1">
                                        <span class="text-sm font-semibold" :class="form.billing_mode === 'custom' ? 'text-purple-700 dark:text-purple-400' : 'text-gray-700 dark:text-gray-300'">Custom</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Custom period</span>
                                    </div>
                                    <svg x-show="form.billing_mode === 'custom'" class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </label>
                            </div>
                        </div>

                        {{-- Pricing Inputs (Conditional based on billing_mode) --}}
                        <div x-show="form.billing_mode === 'monthly'" x-transition.opacity class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="w-8 h-8 rounded-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-sm font-bold text-gray-600 dark:text-gray-300">₱</span>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Monthly Pricing</h3>
                            </div>
                            <div>
                                <input type="hidden" name="prices[0][duration_mode]" value="preset">
                                <input type="hidden" name="prices[0][duration_unit]" value="month">
                                <input type="hidden" name="prices[0][duration_count]" value="1">
                                <input type="hidden" name="prices[0][is_default]" value="1">
                                <input type="hidden" name="prices[0][duration_label]" value="Monthly">
                                <input type="hidden" name="prices[0][currency]" value="PHP">

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Monthly Price (PHP) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">₱</span>
                                     <input type="text"
                                         inputmode="decimal"
                                           x-model="form.prices.monthly.amount_decimal"
                                         @input="updateMoney('monthly', $event.target.value)"
                                           placeholder="199.99"
                                           class="w-full pl-8 pr-3 py-2.5 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:border-purple-500 focus:ring-purple-500/20">
                                    <input type="hidden" name="prices[0][amount_minor]" :value="form.prices.monthly.amount">
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Enter amount in pesos (e.g., 199.99 for ₱199.99/month)</p>
                            </div>
                        </div>

                        <div x-show="form.billing_mode === 'annual'" x-transition.opacity class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="w-8 h-8 rounded-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-sm font-bold text-gray-600 dark:text-gray-300">₱</span>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Annual Pricing</h3>
                            </div>
                            <div>
                                <input type="hidden" name="prices[0][duration_mode]" value="preset">
                                <input type="hidden" name="prices[0][duration_unit]" value="year">
                                <input type="hidden" name="prices[0][duration_count]" value="1">
                                <input type="hidden" name="prices[0][is_default]" value="1">
                                <input type="hidden" name="prices[0][duration_label]" value="Yearly">
                                <input type="hidden" name="prices[0][currency]" value="PHP">

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Annual Price (PHP) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">₱</span>
                                     <input type="text"
                                         inputmode="decimal"
                                           x-model="form.prices.yearly.amount_decimal"
                                         @input="updateMoney('yearly', $event.target.value)"
                                           placeholder="1999.99"
                                           class="w-full pl-8 pr-3 py-2.5 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:border-purple-500 focus:ring-purple-500/20">
                                    <input type="hidden" name="prices[0][amount_minor]" :value="form.prices.yearly.amount">
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Enter amount in pesos (e.g., 1999.99 for ₱1,999.99/year)</p>
                            </div>
                        </div>

                        <div x-show="form.billing_mode === 'custom'" x-transition.opacity class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="w-8 h-8 rounded-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-sm font-bold text-gray-600 dark:text-gray-300">⏱</span>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Custom Period Pricing</h3>
                            </div>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Duration Count <span class="text-red-500">*</span></label>
                                        <input type="number"
                                               x-model="form.prices.custom.duration_count"
                                               min="1"
                                               placeholder="e.g., 3"
                                               class="w-full px-3 py-2.5 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:border-purple-500 focus:ring-purple-500/20">
                                        <input type="hidden" name="prices[0][duration_count]" :value="form.prices.custom.duration_count">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Duration Unit <span class="text-red-500">*</span></label>
                                        <select x-model="form.prices.custom.duration_unit"
                                                class="w-full px-3 py-2.5 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:border-purple-500 focus:ring-purple-500/20">
                                            <option value="day">Days</option>
                                            <option value="week">Weeks</option>
                                            <option value="month">Months</option>
                                            <option value="year">Years</option>
                                        </select>
                                        <input type="hidden" name="prices[0][duration_unit]" :value="form.prices.custom.duration_unit">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Price (PHP) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">₱</span>
                                        <input type="text"
                                               inputmode="decimal"
                                               x-model="form.prices.custom.amount_decimal"
                                               @input="updateMoney('custom', $event.target.value)"
                                               placeholder="499.99"
                                               class="w-full pl-8 pr-3 py-2.5 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:border-purple-500 focus:ring-purple-500/20">
                                        <input type="hidden" name="prices[0][amount_minor]" :value="form.prices.custom.amount">
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">
                                        Renews every <span x-text="form.prices.custom.duration_count"></span>
                                        <span x-text="form.prices.custom.duration_unit + (form.prices.custom.duration_count > 1 ? 's' : '')"></span>
                                    </p>
                                </div>

                                <input type="hidden" name="prices[0][duration_mode]" value="custom">
                                <input type="hidden" name="prices[0][is_default]" value="1">
                                <input type="hidden" name="prices[0][duration_label]" :value="'Every ' + form.prices.custom.duration_count + ' ' + form.prices.custom.duration_unit + (form.prices.custom.duration_count > 1 ? 's' : '')">
                                <input type="hidden" name="prices[0][currency]" value="PHP">
                            </div>
                        </div>
                    </div>

                    {{-- STEP 3: Entitlements (Dynamic based on plan_audience) --}}
                    <div x-show="currentStep === 3" x-transition.opacity class="space-y-6 max-w-4xl mx-auto" x-cloak>
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-2">Feature Entitlements</h3>
                            <div class="mt-3 flex items-start gap-2 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-3 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="text-sm text-indigo-800 dark:text-indigo-300">Configure features available for <strong class="capitalize" x-text="form.plan_audience || 'selected audience'"></strong>. Toggle each feature or set quotas where applicable.</p>
                            </div>
                        </div>

                        {{-- Dynamic Entitlements Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="(feature, index) in availableFeatures" :key="feature.key">
                                <div class="rounded-xl border-2 bg-white dark:bg-gray-800 p-4 shadow-sm hover:border-purple-300 dark:hover:border-purple-700 transition-colors"
                                     :class="form.entitlements[feature.key]?.is_enabled ? 'border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/10' : 'border-gray-200 dark:border-gray-700'">
                                    <label class="flex items-start gap-3 cursor-pointer mb-3">
                                        <input type="checkbox"
                                               :name="'entitlements[' + index + '][is_enabled]'"
                                               value="1"
                                               x-model="form.entitlements[feature.key].is_enabled"
                                               class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <div class="flex-1">
                                            <span class="block font-medium text-gray-900 dark:text-white text-sm" x-text="feature.name"></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="feature.description || getFeatureDescription(feature)"></span>
                                        </div>
                                    </label>

                                    {{-- Show Quota Input for quota-type features --}}
                                    <div x-show="feature.value_type === 'quota' && form.entitlements[feature.key]?.is_enabled" x-collapse class="mt-3 space-y-2">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox"
                                                   :name="'entitlements[' + index + '][is_unlimited]'"
                                                   value="1"
                                                   x-model="form.entitlements[feature.key].is_unlimited"
                                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Unlimited</span>
                                        </div>
                                        <div x-show="!form.entitlements[feature.key].is_unlimited">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Quota Value</label>
                                            <input type="number"
                                                   :name="'entitlements[' + index + '][quota_value]'"
                                                   x-model="form.entitlements[feature.key].quota_value"
                                                   min="0"
                                                   :placeholder="'Enter ' + (feature.unit_label || 'quantity')"
                                                   class="w-full rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:border-purple-500 focus:ring-purple-500/20">
                                        </div>
                                    </div>

                                    {{-- Hidden fields for feature metadata --}}
                                    <input type="hidden" :name="'entitlements[' + index + '][feature_key]'" :value="feature.key">
                                    <input type="hidden" :name="'entitlements[' + index + '][feature_name]'" :value="feature.name">
                                    <input type="hidden" :name="'entitlements[' + index + '][value_type]'" :value="feature.value_type">
                                    <input type="hidden" :name="'entitlements[' + index + '][category]'" :value="feature.category">
                                </div>
                            </template>

                            <div x-show="availableFeatures.length === 0" class="col-span-2 text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                <p class="text-sm">No features available for <span class="font-semibold capitalize" x-text="form.plan_audience"></span></p>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 4: Review & Confirmation --}}
                    <div x-show="currentStep === 4" x-transition.opacity class="space-y-6 max-w-2xl mx-auto" x-cloak>
                        <div class="mb-4 text-center">
                            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 text-green-500 dark:text-green-400 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Final Confirmation</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">Review your plan configuration before saving.</p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700 space-y-3">
                            <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Plan Name</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="form.name || 'Untitled'"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Target Audience</span>
                                <span class="font-semibold text-gray-900 dark:text-white capitalize" x-text="form.plan_audience || 'Not set'"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Billing Mode</span>
                                <span class="font-semibold text-gray-900 dark:text-white capitalize" x-text="form.billing_mode || 'Not set'"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Price</span>
                                <span class="font-semibold text-gray-900 dark:text-white" data-testid="plan-wizard-billing-preview">
                                    <span x-show="form.billing_mode === 'monthly'" x-text="'PHP ' + (form.prices.monthly.amount / 100).toFixed(2) + '/mo'"></span>
                                    <span x-show="form.billing_mode === 'annual'" x-text="'PHP ' + (form.prices.yearly.amount / 100).toFixed(2) + '/yr'"></span>
                                    <span x-show="form.billing_mode === 'custom'" x-text="'PHP ' + (form.prices.custom.amount / 100).toFixed(2)"></span>
                                </span>
                            </div>
                            <div class="flex justify-between items-start pt-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Enabled Features</span>
                                <div class="text-right">
                                    <template x-for="(data, key) in form.entitlements" :key="key">
                                        <span x-show="data.is_enabled" class="block text-sm font-medium text-gray-900 dark:text-white mb-1" x-text="availableFeatures.find(f => f.key === key)?.name || key"></span>
                                    </template>
                                    <span x-show="!Object.values(form.entitlements).some(e => e.is_enabled)" class="text-sm text-gray-400 dark:text-gray-500">No features enabled</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700 mt-6">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <div class="flex items-center h-6">
                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                           class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500 transition-colors cursor-pointer">
                                </div>
                                <div class="flex-1">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-purple-700 dark:group-hover:text-purple-400 transition-colors">Make plan active immediately</span>
                                    <span class="block text-sm text-gray-500 dark:text-gray-400">If checked, this plan will be visible to users right after creation.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between flex-shrink-0 rounded-b-2xl">
                    <button type="button"
                            x-show="currentStep > 1"
                            @click="currentStep--"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        Back
                    </button>
                    <div x-show="currentStep === 1" class="w-20"></div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="closeCreatePlanModal()" class="text-sm font-semibold text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 transition-colors px-4">Cancel</button>

                        <button type="button"
                                x-show="currentStep < 4"
                                @click="nextStep()"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                            Continue
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>

                        <button type="submit"
                                x-show="currentStep === 4"
                                x-cloak
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                            <span x-text="wizardMode === 'edit' ? 'Update Plan' : 'Create Plan'"></span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Impact Modal --}}
    <div x-show="showImpactModal" x-cloak data-testid="plan-impact-modal" class="fixed inset-0 z-[100000]" @keydown.escape.window="closeImpactModal()">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeImpactModal()"></div>
        <div class="relative flex items-center justify-center min-h-full p-4">
             <div class="w-full max-w-md rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl p-6" @click.stop>
                <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="impactActionLabel()"></h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    You are about to <span class="font-semibold lowercase" x-text="impactAction"></span>
                    <span class="font-semibold" x-text="impactPlanName"></span>.
                </p>

                <div class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/30 p-3 text-sm text-gray-700 dark:text-gray-200">
                    <p>Total subscribers: <span class="font-semibold" x-text="impactCounts.total_subscribers"></span></p>
                    <p>Active subscribers: <span class="font-semibold" x-text="impactCounts.active_subscribers"></span></p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Existing subscribers keep their current entitlement until renewal or expiry.</p>
                </div>

                <form method="POST" :action="impactActionUrl()" class="mt-5 flex items-center justify-end gap-3">
                    @csrf
                    <button type="button" @click="closeImpactModal()"
                            class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors"
                            x-text="impactActionLabel()">
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main Table Section --}}
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6 mb-8">
        <div data-testid="admin-table-filter-bar" class="hidden"></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr>
                        <th class="px-5 py-3 bg-gray-50 dark:bg-white/[0.02] text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                        <th class="px-5 py-3 bg-gray-50 dark:bg-white/[0.02] text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan Details</th>
                        <th class="px-5 py-3 bg-gray-50 dark:bg-white/[0.02] text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Audience</th>
                        <th class="px-5 py-3 bg-gray-50 dark:bg-white/[0.02] text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Base Price</th>
                        <th class="px-5 py-3 bg-gray-50 dark:bg-white/[0.02] text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 bg-gray-50 dark:bg-white/[0.02] text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors {{ (int)($highlightPlanId ?? 0) === (int)$plan->id ? 'bg-brand-50/60 dark:bg-brand-500/10' : '' }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-500 dark:text-gray-400">
                                {{ (($plans->currentPage() - 1) * $plans->perPage()) + $loop->iteration }}
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $plan->name }}</p>
                                @if($plan->description)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-1">{{ $plan->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 capitalize">
                                    {{ $plan->plan_audience ?? 'learner' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    ₱{{ number_format($plan->price, 2) }}
                                </span>
                                <span class="text-xs text-gray-400">/mo</span>
                            </td>
                            <td class="px-5 py-4">
                                @if($plan->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.subscription-plans.show', $plan) }}"
                                       class="p-1.5 text-gray-400 hover:text-brand-500 dark:hover:text-brand-400 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-500/10 transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <button type="button"
                                       data-testid="open-edit-plan-modal"
                                       @click="openEditPlanModal({{ $plan->id }})"
                                       class="p-1.5 text-gray-400 hover:text-warning-500 dark:hover:text-warning-400 rounded-lg hover:bg-warning-50 dark:hover:bg-warning-500/10 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="openImpactModal({{ $plan->id }}, @js($plan->name), '{{ $plan->is_active ? 'deactivate' : 'activate' }}')"
                                            class="p-1.5 text-gray-400 hover:text-{{ $plan->is_active ? 'error' : 'success' }}-500 rounded-lg hover:bg-{{ $plan->is_active ? 'error' : 'success' }}-50 dark:hover:bg-{{ $plan->is_active ? 'error' : 'success' }}-500/10 transition-colors"
                                            title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                                            @if($plan->is_active)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                    </button>
                                    <button type="button"
                                            @click="openImpactModal({{ $plan->id }}, @js($plan->name), 'archive')"
                                            class="p-1.5 text-gray-400 hover:text-error-500 dark:hover:text-error-400 rounded-lg hover:bg-error-50 dark:hover:bg-error-500/10 transition-colors" title="Archive">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-400 dark:text-gray-500">No subscription plans found.</p>
                                <button type="button" @click="openCreatePlanModal()" class="mt-2 inline-block text-sm text-brand-500 hover:text-brand-600">Create your first plan →</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Showing {{ $plans->firstItem() }}-{{ $plans->lastItem() }} of {{ $plans->total() }}</p>
                    {{ $plans->withQueryString()->links() }}
                </div>
            </div>
        @endif>    </div>
    </div>

    <script>
        function subscriptionPlansPage(plans, storeRoute) {
            const defaultForm = () => ({
                id: null,
                name: '',
                description: '',
                plan_audience: '',
                billing_mode: 'monthly',
                is_active: true,
                prices: {
                    monthly: { amount: 0, amount_decimal: 0, label: 'Monthly' },
                    yearly: { amount: 0, amount_decimal: 0, label: 'Yearly' },
                    custom: { amount: 0, amount_decimal: 0, duration_count: 1, duration_unit: 'month', label: 'Custom Period' }
                },
                entitlements: {}
            });

            return {
                showCreatePlanModal: false,
                showImpactModal: false,
                impactPlanId: null,
                impactPlanName: '',
                impactAction: 'deactivate',
                impactCounts: {
                    total_subscribers: 0,
                    active_subscribers: 0,
                },
                plans: Array.isArray(plans) ? plans : [],
                wizardMode: 'create',
                currentStep: 1,
                form: defaultForm(),
                featureCatalog: [], // Will be loaded from API

                get availableFeatures() {
                    if (!this.form.plan_audience) return [];
                    return this.featureCatalog;
                },

                getFeatureDescription(feature) {
                    if (feature?.description && feature.description.trim() !== '') {
                        return feature.description;
                    }

                    const cleaned = (feature?.name || feature?.key || 'Feature').replace(/[_-]+/g, ' ').trim();
                    return `Enable ${cleaned.toLowerCase()} for this plan.`;
                },

                normalizeMoney(rawValue) {
                    const sanitized = String(rawValue ?? '')
                        .replace(/[^\d.]/g, '')
                        .replace(/(\..*)\./g, '$1');

                    if (sanitized === '') {
                        return { decimal: '', minor: 0 };
                    }

                    const [whole, fractional = ''] = sanitized.split('.');
                    const trimmedFractional = fractional.slice(0, 2);
                    const decimal = trimmedFractional.length > 0 ? `${whole}.${trimmedFractional}` : whole;
                    const numeric = Number(decimal);

                    return {
                        decimal,
                        minor: Number.isFinite(numeric) && numeric > 0 ? Math.round(numeric * 100) : 0,
                    };
                },

                updateMoney(mode, rawValue) {
                    const normalized = this.normalizeMoney(rawValue);
                    this.form.prices[mode].amount_decimal = normalized.decimal;
                    this.form.prices[mode].amount = normalized.minor;
                },

                async init() {
                    // Fetch feature catalog on component init
                    await this.loadFeatureCatalog();
                },

                async loadFeatureCatalog() {
                    try {
                        const response = await fetch('/admin/api/features', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.featureCatalog = data.features || [];
                            // Initialize entitlements structure
                            this.initializeEntitlements();
                        }
                    } catch (error) {
                        console.error('Failed to load feature catalog:', error);
                        this.featureCatalog = [];
                    }
                },

                initializeEntitlements() {
                    this.featureCatalog.forEach(feature => {
                        if (!this.form.entitlements[feature.key]) {
                            this.form.entitlements[feature.key] = {
                                is_enabled: false,
                                quota_value: null,
                                is_unlimited: false
                            };
                        }
                    });
                },

                openCreatePlanModal() {
                    this.wizardMode = 'create';
                    this.currentStep = 1;
                    this.form = defaultForm();
                    this.initializeEntitlements();
                    this.showCreatePlanModal = true;
                    window.adminSidebarLock?.lock();
                },

                openEditPlanModal(id) {
                    const plan = this.plans.find(p => Number(p.id) === Number(id));
                    if (!plan) return;

                    this.wizardMode = 'edit';
                    this.currentStep = 1;

                    const monthly = plan.plan_prices?.find(p => p.duration_unit === 'month');
                    const yearly = plan.plan_prices?.find(p => p.duration_unit === 'year');
                    const custom = plan.plan_prices?.find(p => p.duration_mode === 'custom')
                        || plan.plan_prices?.find(p => !['month', 'year'].includes(p.duration_unit));

                    const entitlements = {};
                    if (Array.isArray(plan.feature_entitlements)) {
                        plan.feature_entitlements.forEach(ent => {
                            if (ent.feature?.key) {
                                entitlements[ent.feature.key] = {
                                    is_enabled: !!ent.is_enabled,
                                    quota_value: ent.quota_value || null,
                                    is_unlimited: !!ent.is_unlimited
                                };
                            }
                        });
                    }

                    this.form = {
                        id: plan.id,
                        name: plan.name,
                        description: plan.description || '',
                        plan_audience: plan.plan_audience || 'learner',
                        billing_mode: plan.billing_mode || 'monthly',
                        is_active: !!plan.is_active,
                        prices: {
                            monthly: {
                                amount: monthly ? Number(monthly.amount_minor) : 0,
                                amount_decimal: monthly ? (Number(monthly.amount_minor) / 100).toFixed(2) : 0,
                                label: 'Monthly'
                            },
                            yearly: {
                                amount: yearly ? Number(yearly.amount_minor) : 0,
                                amount_decimal: yearly ? (Number(yearly.amount_minor) / 100).toFixed(2) : 0,
                                label: 'Yearly'
                            },
                            custom: {
                                amount: custom ? Number(custom.amount_minor) : 0,
                                amount_decimal: custom ? (Number(custom.amount_minor) / 100).toFixed(2) : 0,
                                duration_count: custom ? Number(custom.duration_count || 1) : 1,
                                duration_unit: custom ? (custom.duration_unit || 'month') : 'month',
                                label: 'Custom Period'
                            }
                        },
                        entitlements: entitlements
                    };

                    this.initializeEntitlements();

                    this.showCreatePlanModal = true;
                    window.adminSidebarLock?.lock();
                },

                closeCreatePlanModal() {
                    this.showCreatePlanModal = false;
                    window.adminSidebarLock?.unlock();
                },

                async openImpactModal(id, name, action) {
                    this.impactPlanId = id;
                    this.impactPlanName = name;
                    this.impactAction = action;
                    this.impactCounts = { total_subscribers: 0, active_subscribers: 0 };
                    this.showImpactModal = true;
                    window.adminSidebarLock?.lock();

                    try {
                        const response = await fetch(`/admin/subscription-plans/${id}/impact`, {
                            headers: { Accept: 'application/json' }
                        });
                        const payload = await response.json();
                        if (payload?.data) {
                            this.impactCounts = payload.data;
                        }
                    } catch (error) {}
                },

                closeImpactModal() {
                    this.showImpactModal = false;
                    this.impactPlanId = null;
                    this.impactPlanName = '';
                    this.impactAction = 'deactivate';
                    window.adminSidebarLock?.unlock();
                },

                wizardAction() {
                    if (this.wizardMode === 'edit' && this.form.id) {
                        return `/admin/subscription-plans/${this.form.id}`;
                    }
                    return storeRoute;
                },

                impactActionUrl() {
                    if (!this.impactPlanId) return '#';
                    if (this.impactAction === 'archive') return `/admin/subscription-plans/${this.impactPlanId}/archive`;
                    return `/admin/subscription-plans/${this.impactPlanId}/toggle`;
                },

                impactActionLabel() {
                    if (this.impactAction === 'archive') return 'Archive Plan';
                    return this.impactAction === 'activate' ? 'Activate Plan' : 'Deactivate Plan';
                },

                nextStep() {
                    if (this.currentStep === 1) {
                        if (!this.form.name || !this.form.name.trim()) {
                            alert('Please enter a plan name.');
                            return;
                        }
                        if (!this.form.plan_audience) {
                            alert('Please select a target audience.');
                            return;
                        }
                        this.currentStep++;
                        return;
                    }

                    if (this.currentStep === 2) {
                        if (!this.form.billing_mode) {
                            alert('Please select a billing mode.');
                            return;
                        }

                        if (this.form.billing_mode === 'monthly' && (!this.form.prices.monthly.amount_decimal || this.form.prices.monthly.amount_decimal <= 0)) {
                            alert('Please enter a valid monthly price.');
                            return;
                        }

                        if (this.form.billing_mode === 'annual' && (!this.form.prices.yearly.amount_decimal || this.form.prices.yearly.amount_decimal <= 0)) {
                            alert('Please enter a valid annual price.');
                            return;
                        }

                        if (this.form.billing_mode === 'custom') {
                            if (!this.form.prices.custom.amount_decimal || this.form.prices.custom.amount_decimal <= 0) {
                                alert('Please enter a valid price.');
                                return;
                            }
                            if (!this.form.prices.custom.duration_count || this.form.prices.custom.duration_count < 1) {
                                alert('Please set a valid duration count.');
                                return;
                            }
                            if (!this.form.prices.custom.duration_unit) {
                                alert('Please select a duration unit.');
                                return;
                            }
                        }

                        this.currentStep++;
                        return;
                    }

                    if (this.currentStep === 3) {
                        this.currentStep++;
                        return;
                    }
                }
            };
        }
    </script>
@endsection
