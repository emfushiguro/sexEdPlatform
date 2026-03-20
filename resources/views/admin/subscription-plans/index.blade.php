@extends('layouts.admin')

@section('title', 'Subscription Plans')
@section('page-title', 'Subscription Plans')

@section('content')
    <div x-data="{
        showCreatePlanModal: false,
        billingMode: 'monthly',
        startDate: '',
        endDate: '',
        openCreatePlanModal() {
            this.showCreatePlanModal = true;
            window.adminSidebarLock?.lock();
        },
        closeCreatePlanModal() {
            this.showCreatePlanModal = false;
            window.adminSidebarLock?.unlock();
        },
        previewRangeText() {
            const format = (dateValue) => new Date(dateValue).toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' });
            if (this.billingMode === 'custom') {
                if (!this.startDate || !this.endDate) {
                    return 'Set start and end date for custom period.';
                }
                return `${format(this.startDate)} to ${format(this.endDate)}`;
            }

            const start = new Date();
            const end = new Date(start);
            if (this.billingMode === 'annual') {
                end.setFullYear(end.getFullYear() + 1);
            } else {
                end.setMonth(end.getMonth() + 1);
            }
            end.setDate(end.getDate() - 1);
            return `${format(start)} to ${format(end)}`;
        }
    }"
    @keydown.escape.window="if (showCreatePlanModal) { closeCreatePlanModal(); }">
    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
        </div>
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

    {{-- Create Plan Modal --}}
    <div x-show="showCreatePlanModal"
         x-cloak
         data-testid="create-plan-fullscreen-modal"
         data-sidebar-lock-hook="create-plan-modal"
         class="fixed inset-0 z-[100000]">
        <div class="absolute inset-0 bg-black/50" @click="closeCreatePlanModal()"></div>
        <div class="relative h-full w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-2xl overflow-y-auto">
            <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Create Subscription Plan</h2>
                <button type="button" @click="closeCreatePlanModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.subscribers.store-plan') }}" class="mx-auto w-full max-w-5xl p-6 space-y-5">
                @csrf
                <div>
                    <label for="modal-plan-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plan Name</label>
                    <input id="modal-plan-name" name="name" type="text" required
                           class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                           placeholder="e.g., Premium Learner">
                </div>

                <div>
                    <label for="modal-plan-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea id="modal-plan-description" name="description" rows="3"
                              class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                              placeholder="Plan summary for admins"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="modal-plan-audience" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plan Audience</label>
                        <select id="modal-plan-audience" name="plan_audience"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <option value="learner" selected>Learner</option>
                            <option value="instructor" disabled>Instructor (future)</option>
                            <option value="connectors" disabled>Connectors (future)</option>
                        </select>
                    </div>
                    <div>
                        <label for="modal-billing-mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Mode</label>
                        <select id="modal-billing-mode" name="billing_mode" x-model="billingMode"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <option value="monthly">Monthly</option>
                            <option value="annual">Annual</option>
                            <option value="custom">Custom Period</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="modal-plan-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Base Price (PHP)</label>
                        <input id="modal-plan-price" name="price" type="number" min="0" step="0.01" value="0.00"
                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>
                    <div class="flex items-center gap-2 pt-6">
                        <input id="modal-plan-active" name="is_active" type="checkbox" value="1" checked class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <label for="modal-plan-active" class="text-sm text-gray-700 dark:text-gray-300">Active plan</label>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/30 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Billing Preview</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200" x-text="previewRangeText()"></p>
                </div>

                <div x-show="billingMode === 'custom'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="modal-start-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input id="modal-start-date" name="start_date" type="date" x-model="startDate"
                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>
                    <div>
                        <label for="modal-end-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                        <input id="modal-end-date" name="end_date" type="date" x-model="endDate"
                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Learner Entitlements</h3>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Account and Profile</p>
                        <div class="grid grid-cols-1 gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[unlimited_username_changes]" value="1" class="rounded border-gray-300"> Unlimited Username Changes</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[profile_customization_perks]" value="1" class="rounded border-gray-300"> Profile Customization Perks (future)</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[early_access_profile_features]" value="1" class="rounded border-gray-300"> Early Access to Profile Features (future)</label>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Learning Access</p>
                        <div class="grid grid-cols-1 gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[certificate_pdf_download_access]" value="1" class="rounded border-gray-300"> Certificate PDF Download</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[premium_module_access]" value="1" class="rounded border-gray-300"> Premium Module Access</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[lesson_attachment_downloads]" value="1" class="rounded border-gray-300"> Lesson Attachment Downloads</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[advanced_topic_bundles]" value="1" class="rounded border-gray-300"> Advanced Topic Bundles</label>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Quiz and Practice</p>
                        <div class="grid grid-cols-1 gap-3 text-sm text-gray-700 dark:text-gray-200">
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[unlimited_shields]" value="1" class="rounded border-gray-300"> Unlimited Shields / Unlimited Quiz Retaking</label>
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 space-y-2">
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="entitlement_enabled[monthly_streak_savers]" value="1" class="rounded border-gray-300"> Monthly Streak Savers</label>
                                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300"><input type="checkbox" name="entitlement_unlimited[monthly_streak_savers]" value="1" class="rounded border-gray-300"> Unlimited</label>
                                <div>
                                    <label for="modal-streak-saver-limit" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Monthly Limit (used when not unlimited)</label>
                                    <input id="modal-streak-saver-limit" name="entitlement_limits[monthly_streak_savers]" type="number" min="0" step="1" value="0"
                                           class="w-36 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="closeCreatePlanModal()"
                            class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors">
                        Create Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Plans</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Active Plans</p>
            <p class="text-3xl font-bold text-success-600 dark:text-success-400">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Inactive Plans</p>
            <p class="text-3xl font-bold text-gray-400 dark:text-gray-500">{{ $stats['inactive'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    @include('admin.partials.table-filter-bar', ['label' => 'Plans Filters', 'hint' => 'Search by plan name/description and status'])
    <form method="GET" action="{{ route('admin.subscription-plans.index') }}"
          class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-4 mb-5 shadow-theme-xs flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Plan name or description…"
                   class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30"/>
        </div>
        <div class="min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors">
            Filter
        </button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.subscription-plans.index') }}"
               class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                Clear
            </a>
        @endif
    </form>
    @include('admin.partials.row-actions', ['actions' => ['Edit', 'Duplicate', 'Archive/Unarchive', 'Reorder']])

    {{-- Plans Table --}}
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Features</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trial</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $plan->name }}</p>
                                @if($plan->description)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-1">{{ $plan->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    ₱{{ number_format($plan->price, 2) }}
                                </span>
                                <span class="text-xs text-gray-400">/mo</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ count($plan->features ?? []) }} feature(s)
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $plan->trial_days ? $plan->trial_days . ' days' : '—' }}
                                </span>
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
                                    <a href="{{ route('admin.subscription-plans.edit', $plan) }}"
                                       class="p-1.5 text-gray-400 hover:text-warning-500 dark:hover:text-warning-400 rounded-lg hover:bg-warning-50 dark:hover:bg-warning-500/10 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.subscription-plans.toggle', $plan) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-{{ $plan->is_active ? 'error' : 'success' }}-500 rounded-lg hover:bg-{{ $plan->is_active ? 'error' : 'success' }}-50 dark:hover:bg-{{ $plan->is_active ? 'error' : 'success' }}-500/10 transition-colors"
                                                title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                                            @if($plan->is_active)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.subscription-plans.delete', $plan) }}" class="inline"
                                          onsubmit="return confirm('Delete this plan? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-error-500 dark:hover:text-error-400 rounded-lg hover:bg-error-50 dark:hover:bg-error-500/10 transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
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
                {{ $plans->withQueryString()->links() }}
            </div>
        @endif
    </div>
    </div>
@endsection
