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
            'started_at' => $subscription->display_started_at ?? 'Not started',
            'expires_at' => $subscription->display_expires_at ?? 'No expiry',
            'details_url' => route('admin.subscribers.show', $subscription),
            'search_blob' => strtolower(implode(' ', array_filter([
                $subscription->id,
                $subscription->user?->name,
                $subscription->user?->email,
                $status,
                $planName,
                $billingLabel,
                $subscription->display_started_at_search,
                $subscription->display_expires_at_search,
            ]))),
        ];
    })->values();
@endphp

@section('content')
    <div x-data="subscriberManagementPage({
            subscriptions: @js($subscriptionRows),
            plans: @js($plans->map(fn ($plan) => ['id' => $plan->id, 'name' => $plan->name])->values()),
            stats: @js($subscriptionStats),
            actionRoutes: {
                archive: @js(route('admin.subscribers.archive', ['subscription' => '__ID__'])),
                destroy: @js(route('admin.subscribers.destroy', ['subscription' => '__ID__'])),
            },
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
            <div class="rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-50 via-white to-brand-100/70 p-5 shadow-theme-xs min-h-[116px]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Subscribers</p>
                        <p class="mt-2 text-4xl leading-none font-bold text-gray-900" x-text="formatNumber(stats.total)"></p>
                    </div>
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg from-brand-500 via-brand-700 to-brand-900 shadow-brand-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H4v-1a4 4 0 014-4h5m0 5v-1a4 4 0 00-4-4H8m5 5h1a4 4 0 004-4v-1m-5-5a3 3 0 11-6 0 3 3 0 016 0zm9 3a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="rounded-[28px] border border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-brand-100/60 p-5 shadow-theme-xs min-h-[116px]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Active</p>
                        <p class="mt-2 text-4xl leading-none font-bold text-gray-900" x-text="formatNumber(stats.active)"></p>
                    </div>
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg from-brand-400 via-brand-600 to-brand-800 shadow-brand-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0Z" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-100/60 via-white to-brand-50 p-5 shadow-theme-xs min-h-[116px]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-800">Revenue</p>
                        <p class="mt-2 text-4xl leading-none font-bold text-gray-900" x-text="formatCurrency(stats.total_revenue)"></p>
                    </div>
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg from-brand-600 via-brand-700 to-brand-900 shadow-brand-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m3.5-9.5c0-1.381-1.567-2.5-3.5-2.5S8.5 7.119 8.5 8.5 10.067 11 12 11s3.5 1.119 3.5 2.5S13.933 16 12 16s-3.5-1.119-3.5-2.5" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="rounded-[28px] border border-brand-300 bg-gradient-to-br from-brand-100 via-white to-brand-200/70 p-5 shadow-theme-xs min-h-[116px]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-900">New This Month</p>
                        <p class="mt-2 text-4xl leading-none font-bold text-gray-900" x-text="formatNumber(stats.new_this_month)"></p>
                    </div>
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg from-brand-700 via-brand-800 to-brand-900 shadow-brand-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div data-testid="admin-table-filter-bar" class="hidden"></div>
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Subscribers Table</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input x-model.debounce.150ms="filters.search"
                                   @input="page = 1"
                                   type="text"
                                   placeholder="Name, email, status, plan, date..."
                                   class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <select x-model="filters.status" @change="page = 1"
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
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
                            <select x-model="filters.planId" @change="page = 1"
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                                <option value="">All plans</option>
                                <template x-for="plan in plans" :key="plan.id">
                                    <option :value="String(plan.id)" x-text="plan.name"></option>
                                </template>
                            </select>
                        </label>
                        <div class="flex items-end">
                            <button type="button"
                                    @click="resetFilters()"
                                    class="w-full rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
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
                        <template x-for="(subscription, index) in paginatedSubscriptions" :key="subscription.id">
                            <tr class="transition hover:bg-brand-50/50">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500" x-text="rowNumber(index)"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700"
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
                                    <div class="flex items-center justify-end gap-2">
                                        <a :href="subscription.details_url"
                                           class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700"
                                           title="View subscriber">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <button type="button"
                                                @click="openActionModal('archive', subscription.id, subscription.subscriber)"
                                                class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700"
                                                title="Archive subscriber">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M6 8l1 10h10l1-10M9 8V6a1 1 0 011-1h4a1 1 0 011 1v2" />
                                            </svg>
                                        </button>

                                        <button type="button"
                                                @click="openActionModal('delete', subscription.id, subscription.subscriber)"
                                                class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700"
                                                title="Delete subscriber">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
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

            <div class="border-t border-gray-100 px-6 py-4 flex items-center justify-end gap-3">
                <div class="flex items-center gap-2">
                    <button type="button" @click="prevPage()" :disabled="page === 1" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
                    <span class="text-sm text-gray-600">Page <span class="font-semibold" x-text="safePage"></span> of <span class="font-semibold" x-text="totalPages"></span></span>
                    <button type="button" @click="nextPage()" :disabled="page >= totalPages" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </div>
        </section>

        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" @keydown.escape.window="closeActionModal()">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeActionModal()"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900" x-text="confirmAction === 'delete' ? 'Delete Subscriber Record?' : 'Archive Subscriber Record?'"></h3>
                <p class="mt-2 text-sm text-gray-600">
                    <span x-show="confirmAction === 'archive'">Archive the subscriber record for </span>
                    <span x-show="confirmAction === 'delete'">Permanently delete the subscriber record for </span>
                    <span class="font-semibold" x-text="confirmTargetLabel || 'this subscriber'"></span>?
                </p>
                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" @click="closeActionModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" @click="submitAction()" :class="confirmAction === 'delete' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'" class="rounded-lg px-4 py-2 text-sm font-semibold text-white">Confirm</button>
                </div>
            </div>
        </div>

        <form method="POST" x-ref="actionForm" class="hidden">
            @csrf
            <input type="hidden" name="_method" value="POST" x-ref="actionMethod">
        </form>
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
                actionRoutes: config.actionRoutes || {},
                page: 1,
                perPage: 10,
                confirmOpen: false,
                confirmAction: 'archive',
                confirmTargetId: null,
                confirmTargetLabel: '',
                get filteredSubscriptions() {
                    return this.subscriptions.filter((subscription) => {
                        const search = this.filters.search.trim().toLowerCase();
                        const matchesSearch = !search || subscription.search_blob.includes(search);
                        const matchesStatus = !this.filters.status || subscription.status === this.filters.status;
                        const matchesPlan = !this.filters.planId || String(subscription.plan_id ?? '') === String(this.filters.planId);

                        return matchesSearch && matchesStatus && matchesPlan;
                    });
                },
                get totalPages() {
                    const pages = Math.ceil(this.filteredSubscriptions.length / this.perPage);
                    return pages > 0 ? pages : 1;
                },
                get safePage() {
                    return Math.min(this.page, this.totalPages);
                },
                get paginatedSubscriptions() {
                    const currentPage = this.safePage;
                    const start = (currentPage - 1) * this.perPage;
                    return this.filteredSubscriptions.slice(start, start + this.perPage);
                },
                resetFilters() {
                    this.filters.search = '';
                    this.filters.status = '';
                    this.filters.planId = '';
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
                openActionModal(action, id, label) {
                    this.confirmAction = action;
                    this.confirmTargetId = id;
                    this.confirmTargetLabel = label;
                    this.confirmOpen = true;
                },
                closeActionModal() {
                    this.confirmOpen = false;
                    this.confirmTargetId = null;
                    this.confirmTargetLabel = '';
                },
                submitAction() {
                    if (!this.confirmTargetId) {
                        return;
                    }

                    const routeTemplate = this.confirmAction === 'delete'
                        ? this.actionRoutes.destroy
                        : this.actionRoutes.archive;

                    this.$refs.actionForm.action = routeTemplate.replace('__ID__', this.confirmTargetId);
                    this.$refs.actionMethod.value = this.confirmAction === 'delete' ? 'DELETE' : 'POST';
                    this.$refs.actionForm.submit();
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
                        trialing: 'bg-brand-100 text-brand-700',
                        cancelled: 'bg-rose-100 text-rose-700',
                        expired: 'bg-gray-100 text-gray-600',
                        past_due: 'bg-amber-100 text-amber-700',
                        grace_period: 'bg-brand-100 text-brand-700',
                        scheduled_cancel: 'bg-amber-100 text-amber-700',
                    }[status] || 'bg-gray-100 text-gray-600';
                },
            };
        }
    </script>
@endsection
