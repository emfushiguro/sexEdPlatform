@extends('layouts.learner-app')

@section('title', 'Payment Pending')

@section('content')
@php
    $paymentContext = $paymentContext ?? 'subscription';
    $redirectUrl = $redirectUrl ?? route('subscription.index');
    $statusUrl = $statusUrl ?? route('payment.status', $payment);
    $historyUrl = $historyUrl ?? route('payment.history');
    $contextLabel = $paymentContext === 'module' ? 'module access' : 'subscription';
@endphp
<div class="max-w-3xl mx-auto space-y-6 py-6">
    @if(session('info'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            {{ session('info') }}
        </div>
    @endif

    @if(session('paymongo_checkout_url') && $payment->isPending())
        <div id="checkout-banner" class="rounded-2xl border border-indigo-200 bg-indigo-50 px-6 py-5">
            <div class="flex items-center justify-center gap-3 mb-2">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-indigo-900 font-semibold">Opening secure payment page...</span>
            </div>
            <p class="text-indigo-700 text-sm text-center">
                You are being redirected to PayMongo. Keep this tab open so your {{ $contextLabel }} can auto-activate once payment is confirmed.
            </p>
            <div class="text-center mt-4">
                <a id="paymongo-link" href="{{ session('paymongo_checkout_url') }}"
                   class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                    Continue to Payment
                </a>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-purple-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-5 text-white" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 45%, #3B0CB1 100%);">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-100">Payment Status</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">{{ $payment->isPending() ? 'Payment Pending' : 'Payment Update' }}</h1>
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
                    <p class="text-xs text-gray-500">Payment Method</p>
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

            <div class="mt-4 rounded-xl border border-gray-200 p-4">
                <p class="text-sm font-semibold text-gray-900 mb-2">What happens next?</p>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start gap-2"><span class="text-purple-600">•</span><span>Once payment is confirmed, your {{ $contextLabel }} activates automatically.</span></li>
                    <li class="flex items-start gap-2"><span class="text-purple-600">•</span><span>You can stay on this page while we poll for status updates.</span></li>
                    <li class="flex items-start gap-2"><span class="text-purple-600">•</span><span>Need to leave? You can return anytime from Payment History.</span></li>
                </ul>
            </div>
        </div>
    </div>

    @if(app()->environment('local'))
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
            <i class="fi fi-rr-arrow-small-left"></i>
            {{ $paymentContext === 'module' ? 'Back to Module' : 'Back to Subscription' }}
        </a>
        <a href="{{ $historyUrl }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
            View Payment History
            <i class="fi fi-rr-arrow-small-right"></i>
        </a>
    </div>
</div>

    @if($payment->isPending())
    <script>
        (function () {
            // ── 1. Auto-redirect to PayMongo checkout ────────────────────────────
            // If the user just submitted the payment form, a checkout URL is waiting
            // in the session. We redirect the same tab to PayMongo after 1.5 s so
            // the user doesn't have to click anything AND this page (which has the
            // polling loop) stays in browser history — Back button returns here.
            @if(session('paymongo_checkout_url'))
            const checkoutUrl = '{{ session('paymongo_checkout_url') }}';
            const banner      = document.getElementById('checkout-banner');

            setTimeout(() => {
                // Update banner text so user knows what's happening
                if (banner) {
                    banner.innerHTML = `
                        <div class="flex items-center justify-center mb-2">
                            <svg class="animate-spin h-6 w-6 text-indigo-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-indigo-800 font-semibold text-lg">Redirecting to PayMongo…</span>
                        </div>`;
                }
                window.location.href = checkoutUrl;
            }, 1500);
            @endif

            // ── 2. Poll for payment confirmation every 4 s ───────────────────────
            // This catches the case where success_url redirect fails (e.g. user
            // closes PayMongo tab, poor connectivity, etc.) and auto-activates.
            const statusUrl   = '{{ $statusUrl }}';
            const pollEvery   = 4000;
            let   attempts    = 0;
            const maxAttempts = 75; // poll for ~5 minutes

            // Floating status badge
            const indicator = document.createElement('div');
            indicator.id = 'poll-indicator';
            indicator.className = 'fixed bottom-4 right-4 bg-white border border-purple-100 shadow-lg rounded-xl px-4 py-3 flex items-center gap-3 text-sm text-gray-600 z-50';
            indicator.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-purple-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="poll-text">Waiting for payment confirmation…</span>`;
            document.body.appendChild(indicator);

            function poll() {
                attempts++;
                fetch(statusUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'completed' && data.redirect) {
                        document.getElementById('poll-text').textContent = '✓ Payment confirmed! Redirecting…';
                        indicator.className = indicator.className.replace('text-gray-600', 'text-green-700');
                        setTimeout(() => { window.location.href = data.redirect; }, 800);
                        return; // stop polling
                    }
                    if (attempts < maxAttempts) setTimeout(poll, pollEvery);
                    else document.getElementById('poll-text').textContent = 'Visit your subscription page to verify payment.';
                })
                .catch(() => {
                    if (attempts < maxAttempts) setTimeout(poll, pollEvery);
                });
            }

            // Start polling after the checkout redirect fires (give it 3 s head start).
            // If there's no redirect (user came back to this page manually), start right away.
            @if(session('paymongo_checkout_url'))
            setTimeout(poll, 3000);
            @else
            setTimeout(poll, pollEvery);
            @endif
        })();
    </script>
    @endif
@endsection
