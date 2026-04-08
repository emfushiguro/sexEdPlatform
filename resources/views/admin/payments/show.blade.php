@extends('layouts.admin')

@section('title', 'Payment Details')
@section('page-title', 'Payment Details')

@php
    $status = is_object($payment->status) ? $payment->status->value : (string) $payment->status;
    $statusClasses = [
        'completed' => 'bg-emerald-100 text-emerald-700',
        'failed' => 'bg-rose-100 text-rose-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-sky-100 text-sky-700',
        'refunded' => 'bg-gray-100 text-gray-600',
    ];
    $user = $payment->user;
    $profile = $user?->profile;
    $learnerProfile = $user?->learnerProfile;
    $gamification = $user?->gamification;
    $subscription = $payment->subscription;
    $modulePurchase = $payment->modulePurchase ?? $payment->moduleSaleLedger?->modulePurchase;
    $module = $modulePurchase?->module ?? $payment->moduleSaleLedger?->module;
    $moduleInstructor = $module?->creator ?? $payment->moduleSaleLedger?->instructor;
    $isModulePurchase = (string) data_get($payment->payment_details, 'payment_scope') === 'module_purchase' || $modulePurchase !== null;
    $transactionType = $isModulePurchase ? 'Module Purchase' : 'Subscription Payment';
    $receiptUrl = route('admin.payments.receipt', $payment);
    $profileLocation = $profile?->location;
    $learnerLocation = collect([$learnerProfile?->barangay?->name ?? null, $learnerProfile?->city?->name ?? null])->filter()->implode(', ');
    $location = $profileLocation ?: ($learnerLocation ?: 'Not provided');
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            <span>Back to Payments</span>
        </a>
        <div class="flex items-center gap-3">
            <a href="{{ $receiptUrl }}" target="_blank" rel="noreferrer" class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 hover:bg-sky-100">View Receipt</a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="xl:col-span-2 space-y-6">
            <div class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Payment Information</p>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Transaction Summary</h2>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                </div>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Transaction Type</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $transactionType }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Amount</p><p class="mt-2 text-3xl font-bold text-emerald-600">₱{{ number_format((float) $payment->amount, 2) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Method</p><p class="mt-2 text-lg font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', (string) ($payment->method ?? 'Unknown'))) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Created</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $payment->created_at->format('M d, Y h:i A') }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Paid At</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $payment->paid_at?->format('M d, Y h:i A') ?? 'Not yet paid' }}</p></div>
                    <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Transaction ID</p><p class="mt-2 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 font-mono text-sm text-gray-700">{{ $payment->transaction_id ?? 'N/A' }}</p></div>
                    @if($payment->invoice)
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Invoice Number</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $payment->invoice->invoice_number ?? 'Generated' }}</p></div>
                    @endif
                </div>
            </div>

            @if($isModulePurchase)
                <div class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Module Purchase Information</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Module Transaction Details</h2>
                    @if($module)
                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Module</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $module->title }}</p></div>
                            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Instructor</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $moduleInstructor?->name ?? 'Unknown instructor' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Module Purchase Status</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ ucfirst((string) ($modulePurchase?->status ?? 'N/A')) }}</p></div>
                            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Purchased At</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $modulePurchase?->purchased_at?->format('M d, Y h:i A') ?? 'N/A' }}</p></div>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500">No module purchase details are linked to this payment record.</p>
                    @endif
                </div>
            @else
                <div class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Subscription Information</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Linked Subscription</h2>
                    @if($subscription)
                    @php
                        $subscriptionStatus = is_object($subscription->status) ? $subscription->status->value : (string) $subscription->status;
                        $currentPlan = $subscription->relationLoaded('plan')
                            ? $subscription->getRelation('plan')
                            : $subscription->plan()->first();
                    @endphp
                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Plan</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $currentPlan?->name ?? $subscription->getPlanLabel() }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Subscription Status</p><span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $subscriptionStatus === 'active' ? 'bg-emerald-100 text-emerald-700' : ($subscriptionStatus === 'expired' ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst(str_replace('_', ' ', $subscriptionStatus)) }}</span></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Billing Window</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->start_date?->format('M d, Y h:i A') ?? 'N/A' }} to {{ $subscription->end_date?->format('M d, Y h:i A') ?? 'N/A' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Auto Renew</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->auto_renew ? 'Enabled' : 'Disabled' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Price Paid</p><p class="mt-2 text-sm font-semibold text-gray-900">₱{{ number_format((float) ($subscription->price_paid ?? 0), 2) }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Plan Duration</p><p class="mt-2 text-sm font-semibold text-gray-900">{{ $subscription->planPrice?->duration_label ?? 'Standard cycle' }}</p></div>
                    </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500">No subscription is linked to this payment record.</p>
                    @endif
                </div>
            @endif
        </section>

        <aside class="space-y-6">
            <div class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">User Information</p>
                <h2 class="mt-2 text-xl font-bold text-gray-900">Account Snapshot</h2>
                <div class="mt-5 flex items-center gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-lg font-bold text-sky-700">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $user?->name ?? 'Unknown user' }}</p>
                        <p class="text-xs text-gray-500">{{ $user?->email ?? 'No email' }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Personal Information</p>
                        <div class="mt-3 space-y-3 rounded-[24px] border border-gray-200 bg-gray-50 p-4 text-sm">
                            <div class="flex items-center justify-between gap-3"><span class="text-gray-500">Username</span><span class="font-semibold text-gray-900">{{ $learnerProfile?->username ?? 'Not set' }}</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-gray-500">Gender</span><span class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $learnerProfile?->gender ?? $profile?->gender ?? 'not provided')) }}</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-gray-500">Location</span><span class="font-semibold text-right text-gray-900">{{ $location }}</span></div>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Gamification Information</p>
                        <div class="mt-3 grid grid-cols-2 gap-3 rounded-[24px] border border-gray-200 bg-gray-50 p-4 text-sm">
                            <div><p class="text-gray-500">Level</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->level ?? 0 }}</p></div>
                            <div><p class="text-gray-500">XP / Score</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->score ?? 0 }}</p></div>
                            <div><p class="text-gray-500">Current Streak</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->streak_count ?? 0 }}</p></div>
                            <div><p class="text-gray-500">Streak Savers</p><p class="mt-1 font-semibold text-gray-900">{{ $gamification?->streak_savers ?? 0 }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
