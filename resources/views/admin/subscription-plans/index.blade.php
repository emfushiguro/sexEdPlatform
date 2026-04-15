@extends('layouts.admin')

@section('title', 'Subscription Plans')
@section('page-title', 'Subscription Plans')

@section('content')
    <div x-data="subscriptionPlansPage(@js($plans), '{{ route('admin.subscription-plans.store') }}', @js($stats))"
         x-init="init()"
         @keydown.escape.window="if (showCreatePlanModal) { closeCreatePlanModal(); }">
    <span class="hidden" data-testid="plan-wizard-mode" x-text="wizardMode"></span>
        <span class="hidden" data-testid="plan-wizard-step-1"></span>
        <span class="hidden" data-testid="plan-wizard-step-2"></span>
        <span class="hidden" data-testid="plan-wizard-step-3"></span>
    <span class="hidden">{{ route('admin.subscribers.store-plan') }}</span>

    {{-- Page Header --}}
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
             <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Plans Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.subscription-plans.archived') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8l1 11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-11M9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                </svg>
                <span>Archived Plans</span>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">{{ $stats['archived'] ?? 0 }}</span>
            </a>
            <button type="button"
               data-testid="open-create-plan-modal"
                  @click="openCreatePlanModal()"
               class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition-colors shadow-theme-xs hover:bg-brand-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Plan
            </button>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[28px] border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-50 p-5 shadow-theme-xs">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Total Plans</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatNumber(stats.total)"></p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-200">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h10M7 12h10M7 17h6M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                    </svg>
                </span>
            </div>
        </div>
        <div class="rounded-[28px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 shadow-theme-xs">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Active Plans</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatNumber(stats.active)"></p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
        </div>
        <div class="rounded-[28px] border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 p-5 shadow-theme-xs">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-600">Inactive Plans</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatNumber(stats.inactive)"></p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-200">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 6h9m-9 6h9m-9 6h9M4.5 6h.008v.008H4.5V6Zm0 6h.008v.008H4.5V12Zm0 6h.008v.008H4.5V18Z"/>
                    </svg>
                </span>
            </div>
        </div>
        <div class="rounded-[28px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 shadow-theme-xs">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">Archived</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatNumber(stats.archived)"></p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-200">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8l1 11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-11M9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                    </svg>
                </span>
            </div>
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
               class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all w-full max-w-4xl max-h-[90vh] flex flex-col">

            {{-- Modal Header with Stepper --}}
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gray-50/70 flex-shrink-0">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900" id="modal-title" x-text="wizardMode === 'edit' ? 'Edit Plan' : 'Create New Subscription Plan'"></h2>
                    <button type="button" @click="closeCreatePlanModal()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 transition-colors">
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
                                         'bg-gradient-to-br from-brand-600 to-brand-700 text-white ring-4 ring-brand-100': currentStep === s,
                                         'bg-brand-600 text-white': currentStep > s,
                                         'bg-white border-2 border-gray-200 text-gray-400': currentStep < s
                                     }">
                                    <template x-if="currentStep > s">
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    </template>
                                    <template x-if="currentStep <= s">
                                        <span class="text-sm font-bold" x-text="s"></span>
                                    </template>
                                </div>
                                <span class="mt-2 text-xs font-semibold uppercase tracking-wider text-center"
                                      :class="currentStep >= s ? 'text-brand-700' : 'text-gray-400'"
                                      x-text="s === 1 ? 'Basics' : (s === 2 ? 'Billing' : (s === 3 ? 'Features' : 'Review'))"></span>
                            </div>
                        </template>
                        <!-- Connecting Lines -->
                        <div class="absolute top-5 left-12 right-12 h-0.5 bg-gray-200 -z-10">
                            <div class="h-full bg-brand-600 transition-all duration-500 ease-in-out"
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

                <div class="p-6 overflow-y-auto flex-1 bg-white">
                    {{-- STEP 1: Plan Basics + Audience --}}
                    <div x-show="currentStep === 1" x-transition.opacity class="space-y-6 max-w-2xl mx-auto">
                        <div class="mb-2">
                            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Plan Basics</h3>
                            <p class="mt-2 text-sm text-gray-500">Define the core identity and target audience for this plan.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Plan Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required
                                   placeholder="e.g., Premium Learner Plan"
                                class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"/>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
                            <textarea name="description" x-model="form.description" rows="3"
                                      placeholder="Describe what makes this plan special for your audience..."
                                      class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 resize-none transition-all"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Plan For <span class="text-red-500">*</span></label>
                            <select name="plan_audience" x-model="form.plan_audience" required
                                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all">
                                <option value="">-- Select Target Audience --</option>
                                <option value="learner">Learner Plan</option>
                                <option value="instructor">Instructor Plan</option>
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500">Choose who this plan is designed for</p>
                        </div>
                    </div>

                    {{-- STEP 2: Flexible Billing Configuration --}}
                    <div x-show="currentStep === 2" x-transition.opacity class="space-y-6 max-w-3xl mx-auto" x-cloak>
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Billing Configuration</h3>
                            <p class="mt-2 text-sm text-gray-500">Choose monthly, annual, or a custom billing period.</p>
                        </div>

                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2.5 2.5M12 3a9 9 0 100 18 9 9 0 000-18z" />
                                    </svg>
                                </span>
                                <h3 class="font-semibold text-gray-900">Billing Period & Pricing</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Billing Period <span class="text-red-500">*</span></label>
                                    <select x-model="form.billing.mode"
                                            class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:border-brand-500 focus:ring-brand-500/20">
                                        <option value="monthly">Monthly</option>
                                        <option value="annual">Annually</option>
                                        <option value="custom">Custom Period</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div x-show="form.billing.mode === 'custom'" x-cloak>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Duration Value <span class="text-red-500">*</span></label>
                                        <input type="number"
                                               x-model.number="form.billing.duration_count"
                                               min="1"
                                               placeholder="e.g., 1"
                                               class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:border-brand-500 focus:ring-brand-500/20">
                                    </div>
                                    <div x-show="form.billing.mode === 'custom'" x-cloak>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Duration Unit <span class="text-red-500">*</span></label>
                                        <select x-model="form.billing.duration_unit"
                                                class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:border-brand-500 focus:ring-brand-500/20">
                                            <option value="minute">Minutes</option>
                                            <option value="hour">Hours</option>
                                            <option value="day">Days</option>
                                            <option value="week">Weeks</option>
                                            <option value="month">Months</option>
                                            <option value="year">Years</option>
                                        </select>
                                    </div>
                                    <div x-show="form.billing.mode !== 'custom'" class="sm:col-span-2 rounded-lg border border-brand-100 bg-brand-50 px-4 py-3" x-cloak>
                                        <p class="text-sm text-brand-800">
                                            <span class="font-semibold" x-text="form.billing.mode === 'monthly' ? 'Monthly preset selected.' : 'Annual preset selected.'"></span>
                                            <span class="ml-1">You can switch to Custom Period if you need a different duration.</span>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Price (PHP) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                                        <input type="text"
                                               inputmode="decimal"
                                               x-model="form.billing.amount_decimal"
                                               @input="updateMoney($event.target.value)"
                                               placeholder="499.99"
                                               class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:border-brand-500 focus:ring-brand-500/20">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1.5">
                                        Renews every <span x-text="effectiveDurationCount()"></span>
                                        <span x-text="durationUnitLabel(effectiveDurationUnit(), effectiveDurationCount())"></span>
                                    </p>
                                </div>

                                <input type="hidden" name="billing_mode" :value="form.billing.mode || 'monthly'">
                                <input type="hidden" name="prices[0][duration_mode]" :value="form.billing.mode === 'custom' ? 'custom' : 'preset'">
                                <input type="hidden" name="prices[0][duration_unit]" :value="effectiveDurationUnit()">
                                <input type="hidden" name="prices[0][duration_count]" :value="effectiveDurationCount()">
                                <input type="hidden" name="prices[0][is_default]" value="1">
                                <input type="hidden" name="prices[0][duration_label]" :value="durationLabel()">
                                <input type="hidden" name="prices[0][currency]" value="PHP">
                                <input type="hidden" name="prices[0][amount_minor]" :value="form.billing.amount">
                            </div>
                        </div>
                    </div>

                    {{-- STEP 3: Entitlements (Dynamic based on plan_audience) --}}
                    <div x-show="currentStep === 3" x-transition.opacity class="space-y-6 max-w-4xl mx-auto" x-cloak>
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Feature Entitlements</h3>
                            <div class="mt-3 flex items-start gap-2 bg-brand-50 border border-brand-100 p-3 rounded-lg">
                                <svg class="w-5 h-5 text-brand-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="text-sm text-brand-800">Configure features available for <strong class="capitalize" x-text="form.plan_audience || 'selected audience'"></strong>. Toggle each feature or set quotas where applicable.</p>
                            </div>
                        </div>

                        {{-- Dynamic Entitlements Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="(feature, index) in availableFeatures" :key="feature.key">
                                <div class="rounded-xl border-2 bg-white p-4 shadow-sm hover:border-brand-300 transition-colors"
                                     :class="form.entitlements[feature.key]?.is_enabled ? 'border-brand-200 bg-brand-50' : 'border-gray-200'">
                                    <label class="flex items-start gap-3 cursor-pointer mb-3">
                                        <input type="checkbox"
                                               :name="'entitlements[' + index + '][is_enabled]'"
                                               value="1"
                                               x-model="form.entitlements[feature.key].is_enabled"
                                               class="mt-1 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        <div class="flex-1">
                                            <span class="block font-medium text-gray-900 text-sm" x-text="feature.name"></span>
                                            <span class="text-xs text-gray-500" x-text="feature.description || getFeatureDescription(feature)"></span>
                                        </div>
                                    </label>

                                    {{-- Show Quota Input for quota-type features --}}
                                    <div x-show="feature.value_type === 'quota' && form.entitlements[feature.key]?.is_enabled" x-collapse class="mt-3 space-y-2">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox"
                                                   :name="'entitlements[' + index + '][is_unlimited]'"
                                                   value="1"
                                                   x-model="form.entitlements[feature.key].is_unlimited"
                                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                              <span class="text-xs font-medium text-gray-700">Unlimited</span>
                                        </div>
                                        <div x-show="!form.entitlements[feature.key].is_unlimited">
                                              <label class="block text-xs font-medium text-gray-600 mb-1">Quota Value</label>
                                            <input type="number"
                                                   :name="'entitlements[' + index + '][quota_value]'"
                                                   x-model="form.entitlements[feature.key].quota_value"
                                                   min="0"
                                                   :placeholder="'Enter ' + (feature.unit_label || 'quantity')"
                                                  class="w-full rounded-lg border border-gray-200 bg-white text-sm focus:border-brand-500 focus:ring-brand-500/20">
                                        </div>
                                    </div>

                                    {{-- Hidden fields for feature metadata --}}
                                    <input type="hidden" :name="'entitlements[' + index + '][feature_key]'" :value="feature.key">
                                    <input type="hidden" :name="'entitlements[' + index + '][feature_name]'" :value="feature.name">
                                    <input type="hidden" :name="'entitlements[' + index + '][value_type]'" :value="feature.value_type">
                                    <input type="hidden" :name="'entitlements[' + index + '][category]'" :value="feature.category">
                                </div>
                            </template>

                            <div x-show="availableFeatures.length === 0" class="col-span-2 text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                <p class="text-sm">No features available for <span class="font-semibold capitalize" x-text="form.plan_audience"></span></p>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 4: Review & Confirmation --}}
                    <div x-show="currentStep === 4" x-transition.opacity class="space-y-6 max-w-2xl mx-auto" x-cloak>
                        <div class="mb-4 text-center">
                            <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Final Confirmation</h3>
                            <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">Review your plan configuration before saving.</p>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-3">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                                <span class="text-sm text-gray-600">Plan Name</span>
                                <span class="font-semibold text-gray-900" x-text="form.name || 'Untitled'"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                                <span class="text-sm text-gray-600">Target Audience</span>
                                <span class="font-semibold text-gray-900 capitalize" x-text="form.plan_audience || 'Not set'"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                                <span class="text-sm text-gray-600">Billing Duration</span>
                                <span class="font-semibold text-gray-900" x-text="durationLabel()"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                                <span class="text-sm text-gray-600">Price</span>
                                <span class="font-semibold text-gray-900" data-testid="plan-wizard-billing-preview">
                                    <span x-text="'PHP ' + (Number(form.billing.amount || 0) / 100).toFixed(2)"></span>
                                </span>
                            </div>
                            <div class="flex justify-between items-start pt-2">
                                <span class="text-sm text-gray-600">Enabled Features</span>
                                <div class="text-right">
                                    <template x-for="(data, key) in form.entitlements" :key="key">
                                        <span x-show="data.is_enabled" class="block text-sm font-medium text-gray-900 mb-1" x-text="availableFeatures.find(f => f.key === key)?.name || key"></span>
                                    </template>
                                    <span x-show="!Object.values(form.entitlements).some(e => e.is_enabled)" class="text-sm text-gray-400">No features enabled</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 mt-6">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <div class="flex items-center h-6">
                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                           class="w-5 h-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 transition-colors cursor-pointer">
                                </div>
                                <div class="flex-1">
                                    <span class="block text-sm font-semibold text-gray-900 mb-1 group-hover:text-brand-700 transition-colors">Make plan active immediately</span>
                                    <span class="block text-sm text-gray-500">If checked, this plan will be visible to users right after creation.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-between flex-shrink-0 rounded-b-2xl">
                    <button type="button"
                            x-show="currentStep > 1"
                            @click="currentStep--"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        Back
                    </button>
                    <div x-show="currentStep === 1" class="w-20"></div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="closeCreatePlanModal()" class="text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors px-4">Cancel</button>

                        <button type="button"
                                x-show="currentStep < 4"
                                @click="nextStep()"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-brand-600 to-brand-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-brand-500/30 transition-all">
                            Continue
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>

                        <button type="submit"
                                x-show="currentStep === 4"
                                x-cloak
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-brand-600 to-brand-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-brand-500/30 transition-all">
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

    <section class="mb-8 overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(244,114,182,0.12),_transparent_28%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div data-testid="admin-table-filter-bar" class="hidden"></div>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Plans Table</h2>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="block xl:col-span-2">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                           <input x-model.debounce.150ms="filters.search"
                               @input="page = 1"
                               type="text"
                               placeholder="Plan, audience, billing, status..."
                               class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                        <select x-model="filters.status" @change="page = 1"
                                class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Audience</span>
                        <select x-model="filters.audience" @change="page = 1"
                                class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                            <option value="">All audiences</option>
                            <option value="learner">Learner</option>
                            <option value="instructor">Instructor</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Billing</span>
                        <select x-model="filters.billing" @change="page = 1"
                                class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                            <option value="">All billing modes</option>
                            <option value="monthly">Monthly</option>
                            <option value="annual">Annual</option>
                            <option value="custom">Custom</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 px-6 py-4">
            <div class="flex items-center gap-3">
                <button type="button"
                        @click="resetFilters()"
                        class="inline-flex items-center rounded-2xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">
                    Reset Filters
                </button>
                <a href="{{ route('admin.subscription-plans.archived') }}"
                   class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8l1 11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-11M9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                    </svg>
                    <span>Archived Plans</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Plan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Audience</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Billing</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Default Price</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Subscribers</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-for="(plan, index) in paginatedPlans" :key="plan.id">
                        <tr class="transition hover:bg-brand-50/50">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-500" x-text="rowNumber(index)"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700"
                                          x-text="plan.name.charAt(0).toUpperCase()"></span>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-gray-900" x-text="plan.name"></p>
                                        <p class="line-clamp-2 text-xs text-gray-500" x-text="plan.description || 'No description added yet.'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize"
                                      :class="audienceClass(plan.plan_audience)"
                                      x-text="plan.plan_audience || 'learner'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900" x-text="formatLabel(plan.billing_mode || 'monthly')"></p>
                                <p class="text-xs text-gray-500" x-text="defaultPriceLabel(plan)"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900" x-text="formatCurrency(defaultPrice(plan).amount)"></p>
                                <p class="text-xs text-gray-500" x-text="defaultPrice(plan).label"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900" x-text="formatNumber(plan.subscriptions_count || 0)"></p>
                                <p class="text-xs text-gray-500"><span x-text="formatNumber(plan.active_subscriptions_count || 0)"></span> active</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold"
                                      :class="statusClass(plan.is_active)"
                                      x-text="plan.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="plan.show_url"
                                       class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-brand-50 text-brand-700 transition hover:bg-brand-100"
                                       title="View">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <button type="button"
                                            data-testid="open-edit-plan-modal"
                                            @click="openEditPlanModal(plan.id)"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                                            title="Edit">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="openImpactModal(plan.id, plan.name, plan.is_active ? 'deactivate' : 'activate')"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border transition"
                                            :class="plan.is_active ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                                            :title="plan.is_active ? 'Deactivate' : 'Activate'">
                                        <template x-if="plan.is_active">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </template>
                                        <template x-if="!plan.is_active">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </template>
                                    </button>
                                    <button type="button"
                                            @click="openImpactModal(plan.id, plan.name, 'archive')"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100"
                                            title="Archive">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredPlans.length === 0" x-cloak>
                        <td colspan="8" class="px-6 py-14 text-center">
                            <div class="mx-auto max-w-sm">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h10M7 12h10M7 17h6M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-sm font-semibold text-gray-900">No plans match these filters</h3>
                                <p class="mt-1 text-sm text-gray-500">Try broadening the search or resetting the column filters.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-6 py-4 flex items-center justify-end gap-3">
            <div class="flex items-center gap-2">
                <button type="button" @click="prevPage()" :disabled="page === 1" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
                <span class="text-sm text-gray-600">Page <span class="font-semibold" x-text="safePage"></span> of <span class="font-semibold" x-text="totalPages"></span></span>
                <button type="button" @click="nextPage()" :disabled="page >= totalPages" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
            </div>
        </div>
    </section>

    {{-- Main Table Section --}}
    <div class="hidden rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6 mb-8">
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
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-500 dark:text-gray-400">
                                {{ $loop->iteration }}
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

        @if(false)
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Showing {{ $plans->firstItem() }}-{{ $plans->lastItem() }} of {{ $plans->total() }}</p>
                    {{ $plans->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>
    </div>

    <script>
        function subscriptionPlansPage(plans, storeRoute, stats) {
            const learnerCoreFeatureKeys = [
                'unlimited_username_change',
                'unlimited_quiz_shields',
                'downloadable_certificates',
            ];

            const learnerCoreFallbackCatalog = [
                {
                    key: 'unlimited_username_change',
                    name: 'Unlimited Username Changes',
                    description: 'Allow learners to change their username without cooldown limits.',
                    value_type: 'boolean',
                    category: 'learner',
                },
                {
                    key: 'unlimited_quiz_shields',
                    name: 'Unlimited Quiz Shields',
                    description: 'Allow unlimited quiz retries without daily shield limits.',
                    value_type: 'boolean',
                    category: 'learner',
                },
                {
                    key: 'downloadable_certificates',
                    name: 'Downloadable PDF Certificates',
                    description: 'Allow learners to download certificate PDFs.',
                    value_type: 'boolean',
                    category: 'learner',
                },
            ];

            const defaultForm = () => ({
                id: null,
                name: '',
                description: '',
                plan_audience: '',
                is_active: true,
                billing: {
                    mode: 'monthly',
                    duration_count: 1,
                    duration_unit: 'month',
                    amount: 0,
                    amount_decimal: '',
                },
                entitlements: {}
            });

            const mapPlan = (plan) => {
                const priceRows = Array.isArray(plan?.plan_prices) ? plan.plan_prices : [];
                const defaultPrice = priceRows.find((price) => price.is_default && price.is_active) || priceRows[0] || null;
                const durationLabel = defaultPrice?.duration_label
                    || (defaultPrice?.duration_unit ? `Every ${defaultPrice.duration_count || 1} ${defaultPrice.duration_unit}${Number(defaultPrice.duration_count || 1) > 1 ? 's' : ''}` : 'No pricing');
                const amount = defaultPrice ? Number(defaultPrice.amount_minor || 0) / 100 : Number(plan?.price || 0);

                return {
                    ...plan,
                    show_url: `/admin/subscription-plans/${plan.id}`,
                    default_price_amount: amount,
                    default_price_label: durationLabel,
                    search_blob: [
                        plan?.name,
                        plan?.description,
                        plan?.plan_audience,
                        plan?.billing_mode,
                        plan?.is_active ? 'active' : 'inactive',
                        durationLabel,
                        amount,
                        plan?.subscriptions_count,
                        plan?.active_subscriptions_count,
                    ].filter(Boolean).join(' ').toLowerCase(),
                };
            };

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
                stats: stats || {},
                plans: Array.isArray(plans) ? plans.map(mapPlan) : [],
                filters: {
                    search: '',
                    status: '',
                    audience: '',
                    billing: '',
                },
                page: 1,
                perPage: 10,
                wizardMode: 'create',
                currentStep: 1,
                form: defaultForm(),
                featureCatalog: [], // Will be loaded from API

                get availableFeatures() {
                    if (!this.form.plan_audience) return [];

                    const catalog = Array.isArray(this.featureCatalog) && this.featureCatalog.length > 0
                        ? this.featureCatalog
                        : learnerCoreFallbackCatalog;

                    if (this.form.plan_audience === 'learner') {
                        return learnerCoreFeatureKeys
                            .map((key) => catalog.find((feature) => feature.key === key))
                            .filter(Boolean);
                    }

                    if (this.form.plan_audience === 'instructor') {
                        return catalog.filter((feature) => ['instructor', 'general'].includes(String(feature.category || '').toLowerCase()));
                    }

                    return [];
                },

                get filteredPlans() {
                    return this.plans.filter((plan) => {
                        const search = this.filters.search.trim().toLowerCase();
                        const matchesSearch = !search || plan.search_blob.includes(search);
                        const matchesStatus = !this.filters.status || (this.filters.status === 'active' ? !!plan.is_active : !plan.is_active);
                        const matchesAudience = !this.filters.audience || String(plan.plan_audience || '') === this.filters.audience;
                        const matchesBilling = !this.filters.billing || String(plan.billing_mode || '') === this.filters.billing;

                        return matchesSearch && matchesStatus && matchesAudience && matchesBilling;
                    });
                },

                get totalPages() {
                    const pages = Math.ceil(this.filteredPlans.length / this.perPage);
                    return pages > 0 ? pages : 1;
                },

                get safePage() {
                    return Math.min(this.page, this.totalPages);
                },

                get paginatedPlans() {
                    const start = (this.safePage - 1) * this.perPage;
                    return this.filteredPlans.slice(start, start + this.perPage);
                },

                resetFilters() {
                    this.filters.search = '';
                    this.filters.status = '';
                    this.filters.audience = '';
                    this.filters.billing = '';
                    this.page = 1;
                },

                rowNumber(index) {
                    return ((this.safePage - 1) * this.perPage) + index + 1;
                },

                prevPage() {
                    if (this.page > 1) {
                        this.page -= 1;
                    }
                },

                nextPage() {
                    if (this.page < this.totalPages) {
                        this.page += 1;
                    }
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('en-US').format(Number(value || 0));
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('en-PH', {
                        style: 'currency',
                        currency: 'PHP',
                        minimumFractionDigits: 2,
                    }).format(Number(value || 0));
                },

                formatLabel(value) {
                    return String(value || '')
                        .replace(/_/g, ' ')
                        .replace(/\b\w/g, (char) => char.toUpperCase());
                },

                defaultPrice(plan) {
                    return {
                        amount: Number(plan?.default_price_amount || 0),
                        label: plan?.default_price_label || 'No pricing',
                    };
                },

                defaultPriceLabel(plan) {
                    return plan?.default_price_label || 'No pricing configured';
                },

                statusClass(isActive) {
                    return isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600';
                },

                audienceClass(audience) {
                    return {
                        learner: 'bg-brand-100 text-brand-700',
                        instructor: 'bg-teal-100 text-teal-700',
                    }[audience] || 'bg-gray-100 text-gray-600';
                },

                getFeatureDescription(feature) {
                    if (feature?.description && feature.description.trim() !== '') {
                        return feature.description;
                    }

                    const cleaned = (feature?.name || feature?.key || 'Feature').replace(/[_-]+/g, ' ').trim();
                    return `Enable ${cleaned.toLowerCase()} for this plan.`;
                },

                normalizeMoney(rawValue) {
                    const incoming = String(rawValue ?? '').trim();
                    const isNegative = incoming.startsWith('-');
                    const unsigned = incoming
                        .replace(/[^\d.]/g, '')
                        .replace(/(\..*)\./g, '$1');

                    if (unsigned === '') {
                        return { decimal: isNegative ? '-' : '', minor: 0 };
                    }

                    const [whole, fractional = ''] = unsigned.split('.');
                    const safeWhole = whole === '' ? '0' : whole;
                    const trimmedFractional = fractional.slice(0, 2);
                    const hasDecimalPoint = unsigned.includes('.');
                    const numericUnsigned = hasDecimalPoint ? `${safeWhole}.${trimmedFractional}` : safeWhole;
                    const displayUnsigned = hasDecimalPoint && unsigned.endsWith('.') && trimmedFractional.length === 0
                        ? `${safeWhole}.`
                        : numericUnsigned;
                    const decimal = isNegative ? `-${displayUnsigned}` : displayUnsigned;
                    const numeric = Number(isNegative ? `-${numericUnsigned}` : numericUnsigned);

                    return {
                        decimal,
                        minor: Number.isFinite(numeric) && numeric > 0 ? Math.round(numeric * 100) : 0,
                    };
                },

                updateMoney(rawValue) {
                    const normalized = this.normalizeMoney(rawValue);
                    this.form.billing.amount_decimal = normalized.decimal;
                    this.form.billing.amount = normalized.minor;
                },

                durationUnitLabel(unit, count = 1) {
                    const singular = {
                        minute: 'minute',
                        hour: 'hour',
                        day: 'day',
                        week: 'week',
                        month: 'month',
                        year: 'year',
                    }[unit] || 'period';

                    return Number(count) === 1 ? singular : `${singular}s`;
                },

                durationLabel() {
                    const count = this.effectiveDurationCount();
                    const unit = this.durationUnitLabel(this.effectiveDurationUnit(), count);
                    return `Every ${count} ${unit}`;
                },

                effectiveDurationUnit() {
                    if (this.form.billing.mode === 'monthly') {
                        return 'month';
                    }

                    if (this.form.billing.mode === 'annual') {
                        return 'year';
                    }

                    return this.form.billing.duration_unit || 'month';
                },

                effectiveDurationCount() {
                    if (this.form.billing.mode === 'monthly' || this.form.billing.mode === 'annual') {
                        return 1;
                    }

                    return Math.max(1, Number(this.form.billing.duration_count || 1));
                },

                billingModeFromDuration() {
                    const count = this.effectiveDurationCount();
                    const unit = this.effectiveDurationUnit();

                    if (unit === 'month' && count === 1) {
                        return 'monthly';
                    }

                    if (unit === 'year' && count === 1) {
                        return 'annual';
                    }

                    return 'custom';
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
                    const featuresToInitialize = Array.isArray(this.featureCatalog) && this.featureCatalog.length > 0
                        ? this.featureCatalog
                        : learnerCoreFallbackCatalog;

                    featuresToInitialize.forEach(feature => {
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

                    const priceRows = Array.isArray(plan.plan_prices) ? plan.plan_prices : [];
                    const defaultPrice = priceRows.find((price) => price.is_default && price.is_active)
                        || priceRows.find((price) => price.is_active)
                        || priceRows[0]
                        || null;
                    const resolvedDurationUnit = defaultPrice ? String(defaultPrice.duration_unit || 'month') : 'month';
                    const resolvedDurationCount = defaultPrice ? Number(defaultPrice.duration_count || 1) : 1;
                    const resolvedMode = (resolvedDurationUnit === 'month' && resolvedDurationCount === 1)
                        ? 'monthly'
                        : ((resolvedDurationUnit === 'year' && resolvedDurationCount === 1) ? 'annual' : 'custom');
                    const fallbackAmountMinor = Math.round(Number(plan.price || 0) * 100);

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
                        is_active: !!plan.is_active,
                        billing: {
                            mode: plan.billing_mode || resolvedMode,
                            duration_count: resolvedDurationCount,
                            duration_unit: resolvedDurationUnit,
                            amount: defaultPrice ? Number(defaultPrice.amount_minor || 0) : fallbackAmountMinor,
                            amount_decimal: defaultPrice
                                ? (Number(defaultPrice.amount_minor || 0) / 100).toFixed(2)
                                : (fallbackAmountMinor > 0 ? (fallbackAmountMinor / 100).toFixed(2) : ''),
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

                notifyValidation(type, message) {
                    if (typeof window.toast !== 'undefined') {
                        if (type === 'warning' && typeof window.toast.warning === 'function') {
                            window.toast.warning(message);
                            return;
                        }
                        if (type === 'error' && typeof window.toast.error === 'function') {
                            window.toast.error(message);
                            return;
                        }
                        if (type === 'info' && typeof window.toast.info === 'function') {
                            window.toast.info(message);
                            return;
                        }
                        if (type === 'success' && typeof window.toast.success === 'function') {
                            window.toast.success(message);
                            return;
                        }

                        if (typeof window.toast.info === 'function') {
                            window.toast.info(message);
                            return;
                        }
                    }

                    if (type === 'warning') {
                        console.warn('[admin.subscription-plans] Toast notifier unavailable:', message);
                        return;
                    }

                    console.error('[admin.subscription-plans] Toast notifier unavailable:', message);
                },

                nextStep() {
                    if (this.currentStep === 1) {
                        if (!this.form.name || !this.form.name.trim()) {
                            this.notifyValidation('warning', 'Please enter a plan name.');
                            return;
                        }
                        if (!this.form.plan_audience) {
                            this.notifyValidation('warning', 'Please select a target audience.');
                            return;
                        }
                        this.currentStep++;
                        return;
                    }

                    if (this.currentStep === 2) {
                        if (!this.form.billing.amount_decimal || this.form.billing.amount_decimal <= 0) {
                            this.notifyValidation('error', 'Please enter a valid plan price.');
                            return;
                        }

                        if (this.form.billing.mode === 'custom' && (!this.form.billing.duration_count || this.form.billing.duration_count < 1)) {
                            this.notifyValidation('error', 'Please set a valid duration value.');
                            return;
                        }

                        if (this.form.billing.mode === 'custom' && !this.form.billing.duration_unit) {
                            this.notifyValidation('warning', 'Please select a duration unit.');
                            return;
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
