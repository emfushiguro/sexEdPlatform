<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Pending') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('info'))
                <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                    {{ session('info') }}
                </div>
            @endif

            @if(session('paymongo_checkout_url') && $payment->isPending())
            <!-- PayMongo Redirect Banner — shown immediately after submitting the payment form -->
            <div id="checkout-banner" class="mb-6 bg-indigo-50 border border-indigo-300 rounded-lg p-6 text-center">
                <div class="flex items-center justify-center mb-3">
                    <svg class="animate-spin h-6 w-6 text-indigo-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-indigo-800 font-semibold text-lg">Opening payment page…</span>
                </div>
                <p class="text-indigo-700 text-sm mb-4">You are being redirected to PayMongo to complete your payment. <strong>Do not close this tab</strong> — your subscription will activate automatically here once payment is confirmed.</p>
                <a id="paymongo-link" href="{{ session('paymongo_checkout_url') }}"
                   class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded transition">
                    Continue to Payment →
                </a>
            </div>
            @endif

            <!-- Payment Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Payment Pending</h3>
                        <p class="text-gray-600">Your payment is being processed. Please wait for confirmation.</p>
                    </div>

                    <!-- Payment Details -->
                    <div class="border-t border-gray-200 pt-4">
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Transaction ID</dt>
                                <dd class="font-medium text-gray-900">{{ $payment->transaction_id }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Amount</dt>
                                <dd class="font-medium text-gray-900">₱{{ number_format($payment->amount, 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Payment Method</dt>
                                <dd class="font-medium text-gray-900">{{ ucfirst($payment->method) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Status</dt>
                                <dd>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $payment->status->value === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($payment->status->value === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($payment->status->value) }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Created</dt>
                                <dd class="font-medium text-gray-900">{{ $payment->created_at->format('M d, Y h:i A') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h4 class="font-semibold text-blue-900 mb-2">What happens next?</h4>
                <ul class="text-blue-800 text-sm space-y-2">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Once your payment is confirmed, your subscription will be activated automatically.</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>You will receive an email confirmation once the payment is processed.</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>If you have any issues, please contact our support team.</span>
                    </li>
                </ul>
            </div>

            <!-- Development Only: Simulate Success -->
            @if(app()->environment('local'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <h4 class="font-semibold text-yellow-800 mb-2">🧪 Development Mode</h4>
                    <p class="text-yellow-700 text-sm mb-4">
                        In production, payments are processed automatically via PayMongo webhooks.
                        For testing, you can simulate a successful payment below.
                    </p>
                    <a href="{{ route('payment.simulate-success', $payment) }}" 
                       class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded transition">
                        ✓ Simulate Successful Payment
                    </a>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <a href="{{ route('subscription.index') }}" class="text-blue-600 hover:text-blue-800">
                    ← Back to Subscription
                </a>
                <a href="{{ route('payment.history') }}" class="text-blue-600 hover:text-blue-800">
                    View Payment History →
                </a>
            </div>
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
            const statusUrl   = '{{ route('payment.status', $payment) }}';
            const pollEvery   = 4000;
            let   attempts    = 0;
            const maxAttempts = 75; // poll for ~5 minutes

            // Floating status badge
            const indicator = document.createElement('div');
            indicator.id = 'poll-indicator';
            indicator.className = 'fixed bottom-4 right-4 bg-white border border-gray-200 shadow-lg rounded-lg px-4 py-3 flex items-center gap-3 text-sm text-gray-600 z-50';
            indicator.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-blue-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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

</x-app-layout>
