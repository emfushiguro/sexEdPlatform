@extends('layouts.learner-app')

@section('title', 'Complete Your Payment')

@section('content')
<div class="max-w-xl mx-auto">

            @if(session('error'))
                <div class="mb-5 rounded-xl px-4 py-3 text-sm text-red-300 border border-red-800" style="background:rgba(127,29,29,0.3);">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="mb-5 rounded-xl px-4 py-3 text-sm text-green-300 border border-green-800" style="background:rgba(20,83,45,0.3);">{{ session('success') }}</div>
            @endif

            {{-- Order Summary Card --}}
            <div class="rounded-2xl p-6 mb-5 border border-gray-700" style="background:#161b2e;">
                <h3 class="text-lg font-bold text-white mb-5">Order Summary</h3>
                <div class="space-y-3 pb-4 mb-4 border-b border-gray-700/60">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm">Plan</span>
                        <span class="font-semibold text-white">{{ $subscription->getPlanLabel() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm">Start Date</span>
                        <span class="font-medium text-gray-200">{{ $subscription->start_date->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm">End Date</span>
                        <span class="font-medium text-gray-200">{{ $subscription->end_date->format('M d, Y') }}</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-white font-bold text-lg">Total Amount</span>
                    <span class="text-2xl font-extrabold text-indigo-400">₱{{ number_format($amount, 2) }}</span>
                </div>
            </div>

            {{-- Payment Method Card --}}
            <div class="rounded-2xl p-6 border border-gray-700" style="background:#161b2e;">
                <h3 class="text-lg font-bold text-white mb-5">Select Payment Method</h3>

                <form action="{{ route('payment.process', $subscription) }}" method="POST" id="payment-form">
                    @csrf

                    <div class="space-y-3">
                        {{-- GCash --}}
                        <label class="flex items-center p-4 rounded-xl cursor-pointer border border-gray-700 transition-all payment-option" style="background:#1a2035;">
                            <input type="radio" name="payment_method" value="gcash" class="h-4 w-4 text-blue-500 border-gray-600">
                            <div class="ml-4 flex items-center gap-3">
                                <div class="w-11 h-11 bg-blue-500 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0">GC</div>
                                <div>
                                    <p class="font-semibold text-white text-sm">GCash</p>
                                    <p class="text-xs text-gray-400">Pay using your GCash wallet</p>
                                </div>
                            </div>
                        </label>

                        {{-- PayMaya --}}
                        <label class="flex items-center p-4 rounded-xl cursor-pointer border border-gray-700 transition-all payment-option" style="background:#1a2035;">
                            <input type="radio" name="payment_method" value="paymaya" class="h-4 w-4 text-green-500 border-gray-600">
                            <div class="ml-4 flex items-center gap-3">
                                <div class="w-11 h-11 bg-green-500 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0">PM</div>
                                <div>
                                    <p class="font-semibold text-white text-sm">PayMaya / Maya</p>
                                    <p class="text-xs text-gray-400">Pay using your Maya wallet</p>
                                </div>
                            </div>
                        </label>

                        {{-- Credit/Debit Card --}}
                        <label class="flex items-center p-4 rounded-xl cursor-pointer border border-gray-700 transition-all payment-option" style="background:#1a2035;">
                            <input type="radio" name="payment_method" value="card" class="h-4 w-4 text-indigo-500 border-gray-600">
                            <div class="ml-4 flex items-center gap-3">
                                <div class="w-11 h-11 bg-gray-700 rounded-xl flex items-center justify-center text-xl flex-shrink-0">💳</div>
                                <div>
                                    <p class="font-semibold text-white text-sm">Credit/Debit Card</p>
                                    <p class="text-xs text-gray-400">Visa, Mastercard, JCB</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    @error('payment_method')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror

                    {{-- Terms --}}
                    <div class="mt-5 p-4 rounded-xl border border-gray-700" style="background:#1a2035;">
                        <label class="flex items-start cursor-pointer gap-3">
                            <input type="checkbox" name="accept_terms" id="accept_terms" required
                                   class="h-4 w-4 text-indigo-500 mt-0.5 border-gray-600 rounded flex-shrink-0">
                            <span class="text-xs text-gray-400 leading-relaxed">
                                I have read and agree to the
                                <a href="{{ route('terms') }}" target="_blank" class="text-indigo-400 hover:text-indigo-300 underline">Terms & Conditions</a>
                                and
                                <a href="{{ route('privacy') }}" target="_blank" class="text-indigo-400 hover:text-indigo-300 underline">Privacy Policy</a>.
                                Refunds are only available within
                                <strong class="text-white">{{ config('billing.subscription.refund_window_days', 3) }} days</strong> of payment.
                            </span>
                        </label>
                        @error('accept_terms')
                            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit" id="submit-btn"
                            class="mt-5 w-full py-4 px-6 rounded-xl font-bold text-white transition disabled:opacity-50 disabled:cursor-not-allowed text-base"
                            style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);">
                        <span id="btn-text">Proceed to Payment</span>
                        <span id="btn-loading" class="hidden">
                            <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </form>
            </div>

            {{-- Security + Back --}}
            <div class="mt-5 flex items-center justify-center gap-2 text-gray-600 text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Secured by PayMongo — Your payment information is encrypted
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('subscription.upgrade') }}" class="text-indigo-400 hover:text-indigo-300 text-sm">← Back to Plans</a>
            </div>
</div>

    <script>
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const acceptTerms   = document.getElementById('accept_terms');
            if (!paymentMethod) { e.preventDefault(); alert('Please select a payment method'); return; }
            if (!acceptTerms.checked) { e.preventDefault(); alert('Please accept the Terms & Conditions'); return; }
            document.getElementById('btn-text').classList.add('hidden');
            document.getElementById('btn-loading').classList.remove('hidden');
            document.getElementById('submit-btn').disabled = true;
        });

        document.querySelectorAll('.payment-option input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.style.borderColor = '#374151';
                    opt.style.background  = '#1a2035';
                });
                if (this.checked) {
                    this.closest('.payment-option').style.borderColor = '#6366f1';
                    this.closest('.payment-option').style.background  = 'rgba(99,102,241,0.15)';
                }
            });
        });
    </script>
@endsection
