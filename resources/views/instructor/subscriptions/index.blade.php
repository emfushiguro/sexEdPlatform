@extends('layouts.instructor-app')

@section('title', 'Instructor Subscriptions')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    @php
        $currentPlanName = $currentPlanDisplayName ?? 'Free Plan';
        $currentStatus = (string) ($currentSubscription?->status?->value ?? 'free');
        $currentStatusLabel = match ($currentStatus) {
            'scheduled_cancel' => 'Scheduled Cancel',
            'grace_period' => 'Grace Period',
            'pending' => 'Pending Payment',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            default => $currentSubscription ? ucfirst(str_replace('_', ' ', $currentStatus)) : 'Free',
        };
        $statusTone = match ($currentStatus) {
            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'grace_period' => 'bg-amber-50 text-amber-700 border-amber-200',
            'pending' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'expired', 'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
        $baselineCard = $baselinePlanCard ?? [];
        $paidCards = $paidPlanCards ?? [];
        $allComparisonCards = array_values(array_merge([$baselineCard], $paidCards));
    @endphp

    <div class="rounded-3xl border border-purple-200 bg-gradient-to-r from-purple-700 via-fuchsia-700 to-indigo-700 p-6 text-white shadow-xl">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-purple-100">Instructor Offers</p>
        <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Upgrade your instructor tools</h1>
                <p class="mt-2 max-w-2xl text-sm text-purple-100">
                    Compare the current free baseline with paid instructor plans that unlock higher publishing caps, paid enrollments, and earnings visibility.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('instructor.payments.history') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20">
                    Payment history
                </a>
                <a href="{{ route('instructor.modules.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/25">
                    Back to modules
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" data-testid="instructor-current-subscription-card">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Current plan</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900">{{ $currentPlanName }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Status:
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $statusTone }}">
                        {{ $currentStatusLabel }}
                    </span>
                    @if($currentSubscription?->ends_at)
                        · Renews or ends {{ $currentSubscription->ends_at->toDayDateTimeString() }}
                    @endif
                </p>
            </div>
            <div class="text-xs text-gray-500">
                Premium access activates only after successful payment confirmation.
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <article class="rounded-3xl border border-purple-200 ring-1 ring-purple-100 bg-white p-6 shadow-sm" data-testid="instructor-free-baseline-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Free baseline</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-gray-900">{{ $baselineCard['name'] ?? 'Free Plan' }}</h2>
                    <p class="mt-2 text-sm text-gray-500">{{ $baselineCard['description'] ?? 'Default instructor access without premium checkout.' }}</p>
                </div>
                <div class="rounded-2xl bg-purple-50 px-4 py-2 text-right">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-purple-500">Price</p>
                    <p class="text-xl font-bold text-purple-900">{{ $baselineCard['price_display'] ?? '0.00' }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-2 text-sm text-gray-600">
                @forelse(($baselineCard['features'] ?? []) as $feature)
                    <div class="flex items-start gap-2">
                        <span class="mt-1 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ !empty($feature['included']) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ !empty($feature['included']) ? '✓' : '−' }}</span>
                        <span>{{ $feature['label'] ?? 'Feature' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No free plan features are configured yet.</p>
                @endforelse
            </div>

            <div class="mt-6">
                @if(!empty($baselineCard['is_current']))
                    <button type="button" disabled class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">
                        Current baseline plan
                    </button>
                @else
                    <button type="button" disabled class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-500">
                        Included by default
                    </button>
                @endif
            </div>
        </article>

        @forelse($paidCards as $plan)
            <article class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Paid</p>
                        <h2 class="mt-1 text-2xl font-extrabold text-gray-900">{{ $plan['name'] ?? 'Premium Plan' }}</h2>
                        @if(!empty($plan['description']))
                            <p class="mt-2 text-sm text-gray-500">{{ $plan['description'] }}</p>
                        @endif
                    </div>
                    <div class="rounded-2xl bg-purple-50 px-4 py-2 text-right">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-purple-500">From</p>
                        <p class="text-xl font-bold text-purple-900">{{ $plan['price_display'] ?? '0.00' }}</p>
                    </div>
                </div>

                <div class="mt-5 space-y-2 text-sm text-gray-600">
                    @forelse(($plan['features'] ?? []) as $feature)
                        <div class="flex items-start gap-2">
                            <span class="mt-1 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ !empty($feature['included']) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ !empty($feature['included']) ? '✓' : '−' }}</span>
                            <span>{{ $feature['label'] ?? 'Feature' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No instructor features configured yet.</p>
                    @endforelse
                </div>

                <div class="mt-6">
                    @if(!empty($plan['is_current']))
                        <button type="button" disabled class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">
                            Current plan
                        </button>
                    @elseif(!empty($plan['is_pending_selection']))
                        @if(!empty($pendingPayment))
                            <a href="{{ route('instructor.payments.pending', $pendingPayment) }}"
                               class="inline-flex w-full items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                Resume pending checkout
                            </a>
                        @else
                            <button type="button" disabled class="inline-flex w-full items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700">
                                Payment pending
                            </button>
                        @endif
                    @else
                        <form method="POST" action="{{ route('instructor.subscriptions.subscribe') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-purple-700 via-fuchsia-700 to-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95">
                                Choose plan
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="lg:col-span-2 rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
                Paid instructor plans are not yet available.
            </div>
        @endforelse
    </div>

    @if(!empty($comparisonRows))
        <section x-data="{ open: true }" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden" data-testid="instructor-feature-comparison-matrix">
            <button type="button" @click="open = !open" class="w-full px-6 py-5 text-left flex items-start justify-between gap-4 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Compare all plan features</h3>
                    <p class="mt-1 text-sm text-gray-500">Availability and limits across free and paid instructor plans.</p>
                </div>
                <span class="inline-flex items-center gap-2 text-sm font-semibold text-purple-700">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
            </button>

            <div x-show="open" x-cloak class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Feature</th>
                            @foreach($allComparisonCards as $comparisonPlan)
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">{{ $comparisonPlan['name'] ?? 'Plan' }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($comparisonRows as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-700">{{ $row['label'] ?? 'Feature' }}</td>
                                @foreach($allComparisonCards as $comparisonPlan)
                                    @php
                                        $cell = data_get($comparisonPlan, 'feature_lookup.' . ($row['key'] ?? ''), null);
                                        $included = (bool) data_get($cell, 'included', false);
                                        $cellLabel = (string) data_get($cell, 'cell_label', 'Not included');
                                    @endphp
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2 {{ $included ? 'text-emerald-700' : 'text-gray-500' }}">
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold {{ $included ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $included ? '✓' : '−' }}</span>
                                            <span>{{ $cellLabel }}</span>
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
