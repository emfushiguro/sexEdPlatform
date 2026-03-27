@extends('layouts.admin')

@section('title', 'Subscription Details')
@section('page-title', 'Subscription Details')

@php
    $status = is_object($subscription->status) ? $subscription->status->value : (string) $subscription->status;
    $statusClasses = [
        'active' => 'bg-emerald-100 text-emerald-700',
        'trialing' => 'bg-sky-100 text-sky-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-sky-100 text-sky-700',
        'past_due' => 'bg-rose-100 text-rose-700',
        'cancelled' => 'bg-gray-100 text-gray-600',
        'expired' => 'bg-gray-100 text-gray-600',
        'grace_period' => 'bg-violet-100 text-violet-700',
        'scheduled_cancel' => 'bg-orange-100 text-orange-700',
    ];
    $user = $subscription->user;
    $profile = $user?->profile;
    $learnerProfile = $user?->learnerProfile;
    $gamification = $user?->gamification;
    $currentPlan = $subscription->relationLoaded('plan')
        ? $subscription->getRelation('plan')
        : $subscription->plan()->first();
    $pricePoint = $subscription->planPrice ?? ($currentPlan?->planPrices?->firstWhere('is_default', true) ?? $currentPlan?->planPrices?->first());
    $profileLocation = $profile?->location;
    $learnerLocation = collect([$learnerProfile?->barangay?->name ?? null, $learnerProfile?->city?->name ?? null])->filter()->implode(', ');
    $location = $profileLocation ?: ($learnerLocation ?: 'Not provided');
    $latestPayment = $subscription->payments->first();
@endphp

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <a href="{{ route('admin.subscribers.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back to Subscribers</span>
                </a>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ $user?->name ?? 'Unknown subscriber' }}</h1>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ \Illuminate\Support\Str::headline($status) }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-500">{{ $user?->email ?? 'No email available' }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($currentPlan)
                    <a href="{{ route('admin.subscription-plans.show', $currentPlan) }}"
                       class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h10M7 17h6M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                        </svg>
                        <span>View Plan</span>
                    </a>
                @endif
                <a href="{{ route('admin.payments.index') }}"
                   class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3z"/>
                    </svg>
                    <span>All Payments</span>
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[28px] border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-cyan-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Current Plan</p>
                <p class="mt-3 text-xl font-bold text-gray-900">{{ $currentPlan?->name ?? $subscription->getPlanLabel() }}</p>
                <p class="mt-2 text-sm text-gray-500">{{ $pricePoint?->duration_label ?? 'Standard billing cycle' }}</p>
            </div>
            <div class="rounded-[28px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Access Window</p>
                <p class="mt-3 text-xl font-bold text-gray-900">{{ $subscription->start_date?->format('M d, Y') ?? 'Not started' }}</p>
                <p class="mt-2 text-sm text-gray-500">Ends {{ $subscription->end_date?->format('M d, Y h:i A') ?? 'without expiry' }}</p>
            </div>
            <div class="rounded-[28px] border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-fuchsia-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-600">Amount Paid</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">&#8369;{{ number_format((float) ($subscription->price_paid ?? 0), 2) }}</p>
                <p class="mt-2 text-sm text-gray-500">{{ $subscription->auto_renew ? 'Auto renew enabled' : 'Auto renew disabled' }}</p>
            </div>
            <div class="rounded-[28px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">Payment Records</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($subscription->payments->count()) }}</p>
                <p class="mt-2 text-sm text-gray-500">Latest status: {{ $latestPayment ? \Illuminate\Support\Str::headline(is_object($latestPayment->status) ? $latestPayment->status->value : (string) $latestPayment->status) : 'No payments yet' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="space-y-6 xl:col-span-2">
                <div class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Subscription Information</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Account and Billing Summary</h2>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Subscription Status</p><p class="mt-2"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-600' }}">{{ \Illuminate\Support\Str::headline($status) }}</span></p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Billing Label</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $pricePoint?->duration_label ?? 'Standard cycle' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Start Date</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->start_date?->format('M d, Y h:i A') ?? 'Not started' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Expiry Date</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->end_date?->format('M d, Y h:i A') ?? 'No expiry date' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Source Provider</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->source_provider ? \Illuminate\Support\Str::headline($subscription->source_provider) : 'Not recorded' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Source Reference</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->source_reference ?? 'Not recorded' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Grace Period Ends</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->grace_period_ends?->format('M d, Y h:i A') ?? $subscription->grace_ends_at?->format('M d, Y h:i A') ?? 'Not in grace period' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Cancelled At</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->cancelled_at?->format('M d, Y h:i A') ?? 'Not cancelled' }}</p></div>
                        <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Cancellation Reason</p><p class="mt-2 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">{{ $subscription->cancellation_reason ?? 'No cancellation reason recorded.' }}</p></div>
                    </div>
                </div>

                <div class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-600">Subscriber Profile</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Personal and Gamification Information</h2>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Personal Information</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-3"><span class="text-gray-500">Username</span><span class="font-semibold text-gray-900">{{ $learnerProfile?->username ?? 'Not set' }}</span></div>
                                <div class="flex items-center justify-between gap-3"><span class="text-gray-500">Gender</span><span class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $learnerProfile?->gender ?? $profile?->gender ?? 'not provided')) }}</span></div>
                                <div class="flex items-center justify-between gap-3"><span class="text-gray-500">Location</span><span class="font-semibold text-right text-gray-900">{{ $location }}</span></div>
                                <div class="flex items-center justify-between gap-3"><span class="text-gray-500">Role</span><span class="font-semibold text-gray-900 capitalize">{{ $user?->role ?? 'Unknown' }}</span></div>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Gamification Information</p>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><p class="text-gray-500">Level</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->level ?? 0 }}</p></div>
                                <div><p class="text-gray-500">XP / Score</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->score ?? 0 }}</p></div>
                                <div><p class="text-gray-500">Current Streak</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->streak_count ?? 0 }}</p></div>
                                <div><p class="text-gray-500">Streak Savers</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->streak_savers ?? 0 }}</p></div>
                                <div><p class="text-gray-500">Total Points</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->total_points ?? 0 }}</p></div>
                                <div><p class="text-gray-500">Longest Streak</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->longest_streak ?? 0 }}</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900">Payment History</h2>
                        <p class="mt-1 text-sm text-gray-500">All payment records linked to this subscriber account.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Amount</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Method</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Date</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($subscription->payments as $payment)
                                    @php $paymentStatus = is_object($payment->status) ? $payment->status->value : (string) $payment->status; @endphp
                                    <tr class="transition hover:bg-sky-50/40">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">&#8369;{{ number_format((float) $payment->amount, 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', (string) ($payment->method ?? 'Unknown'))) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $paymentStatus === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($paymentStatus === 'failed' ? 'bg-rose-100 text-rose-700' : ($paymentStatus === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600')) }}">
                                                {{ \Illuminate\Support\Str::headline($paymentStatus) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('admin.payments.show', $payment) }}"
                                               class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
                                               title="View payment">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No payments are linked to this subscription yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <div class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500">Quick Snapshot</p>
                    <div class="mt-5 flex items-center gap-4">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-lg font-bold text-sky-700">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $user?->name ?? 'Unknown user' }}</p>
                            <p class="text-xs text-gray-500">{{ $user?->email ?? 'No email' }}</p>
                        </div>
                    </div>
                    <div class="mt-6 space-y-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Latest Payment</span>
                            <span class="font-semibold text-gray-900">{{ $latestPayment?->created_at?->format('M d, Y') ?? 'No payment yet' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Transaction Ref</span>
                            <span class="font-mono text-xs font-semibold text-gray-900">{{ $latestPayment?->transaction_id ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
