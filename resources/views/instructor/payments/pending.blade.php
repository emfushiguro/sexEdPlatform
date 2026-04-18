@extends('layouts.instructor-app')

@section('title', 'Payment Pending')

@section('content')
@php
    $paymentContext = $paymentContext ?? 'subscription';
    $redirectUrl = $redirectUrl ?? route('instructor.subscriptions.index');
    $statusUrl = $statusUrl ?? route('instructor.payments.status', $payment);
    $historyUrl = $historyUrl ?? route('instructor.payments.history');
@endphp
<div class="max-w-3xl mx-auto space-y-6 py-6">
    @if(session('paymongo_checkout_url') && $payment->isPending())
        <div id="checkout-banner" class="rounded-2xl border border-indigo-200 bg-indigo-50 px-6 py-5">
            <div class="flex items-center justify-center gap-3 mb-2">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-indigo-900 font-semibold">Opening secure payment page...</span>
            </div>
            <p class="text-indigo-700 text-sm text-center">Keep this tab open so your instructor subscription can auto-activate after payment confirmation.</p>
        </div>
    @endif

    <div class="rounded-2xl border border-purple-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-5 text-white" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 45%, #3B0CB1 100%);">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-100">Instructor Payment</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Payment Pending</h1>
            <p class="mt-1 text-sm text-purple-100">We are checking your transaction in real time.</p>
        </div>

        <div class="bg-white px-6 py-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Transaction ID</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 break-all">{{ $payment->transaction_id }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Amount</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">PHP {{ number_format($payment->amount, 2) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Method</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $payment->method ? ucwords(str_replace('_', ' ', $payment->method)) : 'To be selected' }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Status</p>
                    <p class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $payment->status->value === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($payment->status->value === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                            {{ ucfirst($payment->status->value) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(app()->environment((array) config('billing.payment.simulation_enabled_envs', ['local', 'testing', 'staging'])) && $payment->isPending())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
            <p class="text-sm font-semibold text-amber-800">Development Mode</p>
            <p class="text-sm text-amber-700 mt-1 mb-3">Use simulation for local testing when gateway callbacks are unavailable.</p>
            <a href="{{ route('payment.simulate-success', $payment) }}"
               class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                Simulate Successful Payment
            </a>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $redirectUrl }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            Back to subscriptions
        </a>
        <a href="{{ $historyUrl }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
            View Payment History
        </a>
    </div>
</div>

@if($payment->isPending())
<script>
    (function () {
        @if(session('paymongo_checkout_url'))
        const checkoutUrl = '{{ session('paymongo_checkout_url') }}';
        setTimeout(() => { window.location.href = checkoutUrl; }, 1200);
        @endif

        const statusUrl = '{{ $statusUrl }}';
        const pollEvery = 4000;
        let attempts = 0;
        const maxAttempts = 75;

        function poll() {
            attempts++;
            fetch(statusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'completed' && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (attempts < maxAttempts) {
                    setTimeout(poll, pollEvery);
                }
            })
            .catch(() => {
                if (attempts < maxAttempts) {
                    setTimeout(poll, pollEvery);
                }
            });
        }

        @if(session('paymongo_checkout_url'))
        setTimeout(poll, 3000);
        @else
        setTimeout(poll, pollEvery);
        @endif
    })();
</script>
@endif
@endsection
