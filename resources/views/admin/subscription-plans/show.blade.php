@extends('layouts.admin')

@section('title', $subscriptionPlan->name)
@section('page-title', 'Plan: ' . $subscriptionPlan->name)

@php
    $pricePoints = $subscriptionPlan->planPrices->sortByDesc('is_default');
    $enabledEntitlements = $subscriptionPlan->featureEntitlements->where('is_enabled', true)->values();
@endphp

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <a href="{{ route('admin.subscription-plans.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back to Plans</span>
                </a>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ $subscriptionPlan->name }}</h1>
                    @if($subscriptionPlan->is_active)
                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Active</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">Inactive</span>
                    @endif
                    <span class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-bold capitalize text-brand-700">{{ $subscriptionPlan->plan_audience ?? 'learner' }}</span>
                </div>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">{{ $subscriptionPlan->description ?: 'No plan description added yet.' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.subscription-plans.index', ['highlight_plan' => $subscriptionPlan->id, 'edit_plan' => $subscriptionPlan->id]) }}"
                   class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                   title="Edit plan">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
                <form method="POST" action="{{ route('admin.subscription-plans.toggle', $subscriptionPlan) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border transition {{ $subscriptionPlan->is_active ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                            title="{{ $subscriptionPlan->is_active ? 'Deactivate plan' : 'Activate plan' }}">
                        @if($subscriptionPlan->is_active)
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        @else
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.subscription-plans.archive', $subscriptionPlan) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100"
                            title="Archive plan">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8l1 11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-11M9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[28px] border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Price Points</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($stats['price_points']) }}</p>
                <p class="mt-2 text-sm text-gray-500">Billing options configured for this plan.</p>
            </div>
            <div class="rounded-[28px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Active Subscribers</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($stats['active_subscribers']) }}</p>
                <p class="mt-2 text-sm text-gray-500">Subscribers currently receiving access through this plan.</p>
            </div>
            <div class="rounded-[28px] border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Enabled Entitlements</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($stats['enabled_entitlements']) }}</p>
                <p class="mt-2 text-sm text-gray-500">Features and quotas currently granted by this plan.</p>
            </div>
            <div class="rounded-[28px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">Recurring Revenue</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">&#8369;{{ number_format($stats['monthly_revenue'], 2) }}</p>
                <p class="mt-2 text-sm text-gray-500">Value currently attached to active subscribers.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <section class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Pricing Matrix</p>
                            <h2 class="mt-2 text-xl font-bold text-gray-900">Billing and Availability</h2>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @forelse($pricePoints as $price)
                            <div class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $price->duration_label }}</p>
                                        <p class="mt-2 text-2xl font-bold text-gray-900">&#8369;{{ number_format(((int) $price->amount_minor) / 100, 2) }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ ucfirst($price->duration_mode) }} · {{ $price->duration_count }} {{ \Illuminate\Support\Str::plural($price->duration_unit, $price->duration_count) }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        @if($price->is_default)
                                            <span class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-brand-700">Default</span>
                                        @endif
                                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide {{ $price->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $price->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-gray-200 bg-gray-50 p-5 text-sm text-gray-500">
                                No detailed pricing rows are attached to this plan yet.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl border border-gray-200 bg-white p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500">Plan Setup</p>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Billing Mode</dt>
                                    <dd class="font-semibold text-gray-900">{{ ucfirst($subscriptionPlan->billing_mode ?? 'monthly') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Base Price</dt>
                                    <dd class="font-semibold text-gray-900">&#8369;{{ number_format((float) $subscriptionPlan->price, 2) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Plan Audience</dt>
                                    <dd class="font-semibold capitalize text-gray-900">{{ $subscriptionPlan->plan_audience ?? 'learner' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Slug</dt>
                                    <dd class="rounded-full bg-gray-100 px-3 py-1 font-mono text-xs text-gray-700">{{ $subscriptionPlan->slug }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </section>

                <section class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Entitlements</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">What This Plan Unlocks</h2>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @forelse($enabledEntitlements as $entitlement)
                            <div class="rounded-3xl border border-brand-100 bg-brand-50/50 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $entitlement->feature?->name ?? $entitlement->feature?->key ?? 'Feature' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $entitlement->feature?->description ?: 'Enabled entitlement for this plan.' }}</p>
                                    </div>
                                    @if($entitlement->is_unlimited)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">Unlimited</span>
                                    @elseif(!is_null($entitlement->quota_value))
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-700">Quota {{ $entitlement->quota_value }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-gray-200 bg-gray-50 p-5 text-sm text-gray-500">
                                No enabled entitlements are attached to this plan yet.
                            </div>
                        @endforelse
                    </div>

                    @if(!empty($subscriptionPlan->features))
                        <div class="mt-6 rounded-3xl border border-gray-200 bg-white p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500">Legacy Feature Keys</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($subscriptionPlan->features as $feature)
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">{{ $feature }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900">Subscribers</h2>
                        <p class="mt-1 text-sm text-gray-500">Current and historical subscriber records linked to this plan.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Subscriber</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Started</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Expires</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($subscriptionPlan->subscriptions as $subscription)
                                    @php
                                        $status = is_object($subscription->status) ? $subscription->status->value : (string) $subscription->status;
                                        $statusClass = match ($status) {
                                            'active' => 'bg-emerald-100 text-emerald-700',
                                            'trialing' => 'bg-brand-100 text-brand-700',
                                            'past_due' => 'bg-amber-100 text-amber-700',
                                            'cancelled', 'expired' => 'bg-gray-100 text-gray-600',
                                            default => 'bg-brand-100 text-brand-700',
                                        };
                                    @endphp
                                    <tr class="transition hover:bg-brand-50/40">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700">
                                                    {{ strtoupper(substr($subscription->user->name ?? '?', 0, 1)) }}
                                                </span>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900">{{ $subscription->user->name ?? 'Unknown' }}</p>
                                                    <p class="text-xs text-gray-500">{{ $subscription->user->email ?? 'No email available' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                                {{ \Illuminate\Support\Str::headline($status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $subscription->start_date?->format('M d, Y h:i A') ?? 'Not started' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $subscription->end_date?->format('M d, Y h:i A') ?? 'No expiry' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No subscribers are linked to this plan yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-[30px] border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500">Snapshot</p>
                    <div class="mt-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Total Subscribers</span>
                            <span class="text-sm font-bold text-gray-900">{{ number_format($stats['total_subscribers']) }}</span>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
