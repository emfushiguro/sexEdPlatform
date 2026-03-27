@extends('layouts.admin')

@section('title', 'Subscriber Management')
@section('page-title', 'Subscribers')

@php
    $subscriptionRows = $subscriptions->map(function ($subscription) {
        $status = is_object($subscription->status) ? $subscription->status->value : (string) $subscription->status;
        $currentPlan = $subscription->relationLoaded('plan')
            ? $subscription->getRelation('plan')
            : $subscription->plan()->first();
        $planName = $currentPlan?->name ?? $subscription->getPlanLabel();
        $billingLabel = $subscription->planPrice?->duration_label
            ?? (($subscription->planPrice?->duration_count ?? null) && ($subscription->planPrice?->duration_unit ?? null)
                ? $subscription->planPrice->duration_count . ' ' . \Illuminate\Support\Str::plural($subscription->planPrice->duration_unit, $subscription->planPrice->duration_count)
                : 'Standard');

        return [
            'id' => $subscription->id,
            'subscriber' => $subscription->user?->name ?? 'Unknown subscriber',
            'email' => $subscription->user?->email ?? 'No email',
            'status' => $status,
            'plan_id' => $subscription->plan_id,
            'plan' => $planName,
            'billing' => $billingLabel,
            'price_paid' => (float) ($subscription->price_paid ?? 0),
            'started_at' => optional($subscription->start_date)->format('M d, Y h:i A') ?? 'Not started',
            'expires_at' => optional($subscription->end_date)->format('M d, Y h:i A') ?? 'No expiry',
            'details_url' => route('admin.subscribers.show', $subscription),
            'search_blob' => strtolower(implode(' ', array_filter([
                $subscription->id,
                $subscription->user?->name,
                $subscription->user?->email,
                $status,
                $planName,
                $billingLabel,
                optional($subscription->start_date)->format('Y-m-d H:i'),
                optional($subscription->end_date)->format('Y-m-d H:i'),
            ]))),
        ];
    })->values();
@endphp

@section('content')
    <div x-data="subscriberManagementPage({
            subscriptions: @js($subscriptionRows),
            plans: @js($plans->map(fn ($plan) => ['id' => $plan->id, 'name' => $plan->name])->values()),
            stats: @js($subscriptionStats),
            initial: {
                search: @js((string) request('search', '')),
                status: @js((string) request('status', '')),
                planId: @js((string) request('plan_id', '')),
            }
        })"
         class="space-y-8">

        <div class="flex flex-col gap-2">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Subscriber Management</h1>
            <p class="max-w-3xl text-sm leading-6 text-gray-500">
                Review subscriber status, live access windows, and payment-backed plan assignments from one place.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[28px] border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-cyan-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Subscribers</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatNumber(stats.total)"></p>
                        <p class="mt-2 text-sm text-gray-500">All subscriber records currently tracked in admin.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500 text-white shadow-lg shadow-sky-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H4v-1a4 4 0 014-4h5m0 5v-1a4 4 0 00-4-4H8m5 5h1a4 4 0 004-4v-1m-5-5a3 3 0 11-6 0 3 3 0 016 0zm9 3a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="rounded-[28px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Active</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatNumber(stats.active)"></p>
                        <p class="mt-2 text-sm text-gray-500">Subscribers with access currently enabled.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0Z" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="rounded-[28px] border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-fuchsia-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-600">Revenue</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatCurrency(stats.total_revenue)"></p>
                        <p class="mt-2 text-sm text-gray-500">Total active subscription value currently on record.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg shadow-violet-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m3.5-9.5c0-1.381-1.567-2.5-3.5-2.5S8.5 7.119 8.5 8.5 10.067 11 12 11s3.5 1.119 3.5 2.5S13.933 16 12 16s-3.5-1.119-3.5-2.5" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="rounded-[28px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">New This Month</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900" x-text="formatNumber(stats.new_this_month)"></p>
                        <p class="mt-2 text-sm text-gray-500">Fresh subscriptions created during the current month.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(244,114,182,0.12),_transparent_28%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-6">
                <div data-testid="admin-table-filter-bar" class="hidden"></div>
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Live Filters</p>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Subscribers Table</h2>
                        <p class="mt-1 text-sm text-gray-500">Search every visible column in real time and narrow the table with column-specific filters.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input x-model.debounce.150ms="filters.search"
                                   type="text"
                                   placeholder="Name, email, status, plan, date..."
                                   class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <select x-model="filters.status"
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">All statuses</option>
                                <option value="active">Active</option>
                                <option value="trialing">Trialing</option>
                                <option value="grace_period">Grace Period</option>
                                <option value="scheduled_cancel">Scheduled Cancel</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="expired">Expired</option>
                                <option value="past_due">Past Due</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Plan</span>
                            <select x-model="filters.planId"
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">All plans</option>
                                <template x-for="plan in plans" :key="plan.id">
                                    <option :value="String(plan.id)" x-text="plan.name"></option>
                                </template>
                            </select>
                        </label>
                        <div class="flex items-end">
                            <button type="button"
                                    @click="resetFilters()"
                                    class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900"><span x-text="filteredSubscriptions.length"></span> matching subscribers</p>
                    <p class="text-xs text-gray-500">Live filtering updates as you type and select columns.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-600">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <span>Showing current admin dataset</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Subscriber</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Plan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Billing</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Started</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Expires</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="(subscription, index) in filteredSubscriptions" :key="subscription.id">
                            <tr class="transition hover:bg-sky-50/50">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500" x-text="index + 1"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-bold text-sky-700"
                                              x-text="subscription.subscriber.charAt(0).toUpperCase()"></span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900" x-text="subscription.subscriber"></p>
                                            <p class="text-xs text-gray-500" x-text="subscription.email"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold"
                                          :class="statusClass(subscription.status)"
                                          x-text="formatStatus(subscription.status)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900" x-text="subscription.plan"></p>
                                    <p class="text-xs text-gray-500" x-text="formatCurrency(subscription.price_paid)"></p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="subscription.billing"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="subscription.started_at"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="subscription.expires_at"></td>
                                <td class="px-6 py-4 text-right">
                                    <a :href="subscription.details_url"
                                       class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
                                       title="View subscriber">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredSubscriptions.length === 0" x-cloak>
                            <td colspan="8" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H4v-1a4 4 0 014-4h5m0 5v-1a4 4 0 00-4-4H8m5 5h1a4 4 0 004-4v-1m-5-5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="mt-4 text-sm font-semibold text-gray-900">No subscribers match these filters</h3>
                                    <p class="mt-1 text-sm text-gray-500">Try broadening the search or resetting one of the column filters.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        function subscriberManagementPage(config) {
            return {
                subscriptions: config.subscriptions || [],
                plans: config.plans || [],
                stats: config.stats || {},
                filters: {
                    search: config.initial?.search || '',
                    status: config.initial?.status || '',
                    planId: config.initial?.planId || '',
                },
                get filteredSubscriptions() {
                    return this.subscriptions.filter((subscription) => {
                        const search = this.filters.search.trim().toLowerCase();
                        const matchesSearch = !search || subscription.search_blob.includes(search);
                        const matchesStatus = !this.filters.status || subscription.status === this.filters.status;
                        const matchesPlan = !this.filters.planId || String(subscription.plan_id ?? '') === String(this.filters.planId);

                        return matchesSearch && matchesStatus && matchesPlan;
                    });
                },
                resetFilters() {
                    this.filters.search = '';
                    this.filters.status = '';
                    this.filters.planId = '';
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
                formatStatus(status) {
                    return String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
                },
                statusClass(status) {
                    return {
                        active: 'bg-emerald-100 text-emerald-700',
                        trialing: 'bg-sky-100 text-sky-700',
                        cancelled: 'bg-rose-100 text-rose-700',
                        expired: 'bg-gray-100 text-gray-600',
                        past_due: 'bg-amber-100 text-amber-700',
                        grace_period: 'bg-violet-100 text-violet-700',
                        scheduled_cancel: 'bg-orange-100 text-orange-700',
                    }[status] || 'bg-gray-100 text-gray-600';
                },
            };
        }
    </script>
@endsection
