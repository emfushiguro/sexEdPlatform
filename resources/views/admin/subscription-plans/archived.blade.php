@extends('layouts.admin')

@section('title', 'Archived Plans')
@section('page-title', 'Archived Plans')

@php
    $archivedStats = [
        'total' => $plans->total(),
        'with_subscribers' => $plans->getCollection()->where('subscriptions_count', '>', 0)->count(),
        'active_subscribers' => $plans->getCollection()->sum('active_subscriptions_count'),
    ];
@endphp

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Archived Plans</h1>
                <p class="mt-1 text-sm text-gray-500">Review archived plans, inspect what they used to contain, and restore them back to the active catalog when needed.</p>
            </div>
            <a href="{{ route('admin.subscription-plans.index') }}"
               class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Back to Plans</span>
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-[28px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">Archived Plans</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($archivedStats['total']) }}</p>
                        <p class="mt-2 text-sm text-gray-500">Plans currently removed from the live catalog.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8l1 11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-11M9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="rounded-[28px] border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Plans With History</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($archivedStats['with_subscribers']) }}</p>
                        <p class="mt-2 text-sm text-gray-500">Archived plans that still have subscriber records attached.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-1a4 4 0 0 0-4-4h-1m-4 5H4v-1a4 4 0 0 1 4-4h5m0 5v-1a4 4 0 0 0-4-4H8m5 5h1a4 4 0 0 0 4-4v-1m-5-5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="rounded-[28px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Active Subscribers Impacted</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($archivedStats['active_subscribers']) }}</p>
                        <p class="mt-2 text-sm text-gray-500">Active subscriber records still linked to archived plans.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(251,191,36,0.18),_transparent_30%),linear-gradient(180deg,#ffffff_0%,#fffaf0_100%)] px-6 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">Archive Registry</p>
                <h2 class="mt-2 text-xl font-bold text-gray-900">Archived Plan Records</h2>
                <p class="mt-1 text-sm text-gray-500">These plans are hidden from the main catalog until you restore them. Restore brings them back as inactive so you can review before reactivating.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Plan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Billing</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Subscribers</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Archived</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($plans as $plan)
                            @php
                                $defaultPrice = $plan->planPrices->firstWhere('is_default', true) ?? $plan->planPrices->first();
                            @endphp
                            <tr class="transition hover:bg-amber-50/40">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ $plans->firstItem() + $loop->index }}</td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $plan->name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $plan->description ?: 'No description added.' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ ucfirst($plan->billing_mode ?? 'monthly') }}</p>
                                    <p class="text-xs text-gray-500">
                                        @if($defaultPrice)
                                            &#8369;{{ number_format(((int) $defaultPrice->amount_minor) / 100, 2) }} · {{ $defaultPrice->duration_label }}
                                        @else
                                            No pricing saved
                                        @endif
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ number_format($plan->subscriptions_count) }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($plan->active_subscriptions_count) }} active</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ optional($plan->archived_at)->format('M d, Y h:i A') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.subscription-plans.show', $plan) }}"
                                           class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-brand-50 text-brand-700 transition hover:bg-brand-100"
                                           title="View">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.subscription-plans.restore', $plan) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"
                                                    title="Restore">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 5.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="mx-auto max-w-sm">
                                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8l1 11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-11M9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                                            </svg>
                                        </div>
                                        <h3 class="mt-4 text-sm font-semibold text-gray-900">No archived plans found</h3>
                                        <p class="mt-1 text-sm text-gray-500">Once plans are archived, they’ll appear here for review and restore.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($plans->hasPages())
                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $plans->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
