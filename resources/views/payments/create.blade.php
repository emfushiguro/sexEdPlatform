@extends('layouts.learner-app')

@section('title', 'Complete Your Payment')

@section('content')
<div class="max-w-3xl mx-auto space-y-6 py-6">
    <div class="rounded-2xl border border-purple-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-5 text-white" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 45%, #3B0CB1 100%);">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-100">Secure Checkout</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Complete Your Payment</h1>
            <p class="mt-1 text-sm text-purple-100">Choose a payment method to activate your subscription.</p>
        </div>
        <div class="px-6 py-4 bg-white">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Plan</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $subscription->getPlanLabel() }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Starts</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $subscription->start_date->format('M d, Y') }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Ends</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $subscription->end_date->format('M d, Y') }}</p>
                </div>
            </div>
            <div class="mt-4 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-purple-700">Total Amount</span>
                <span class="text-2xl font-extrabold tracking-tight text-purple-800">PHP {{ number_format($amount, 2) }}</span>
            </div>
        </div>
    </div>

    <form action="{{ route('payment.process', $subscription) }}" method="POST" id="payment-form" x-data="{ selectedMethod: '{{ old('payment_method', '') }}' }" class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
        @csrf

        <h2 class="text-lg font-bold text-gray-900">Select Payment Method</h2>
        <p class="mt-1 text-sm text-gray-500">You will be redirected to a secure payment provider after submission.</p>

        <div class="mt-5 space-y-3">
            <label class="flex items-center p-4 rounded-xl cursor-pointer border transition-all duration-200"
                   :class="selectedMethod === 'gcash' ? 'border-blue-300 bg-blue-50/80 ring-1 ring-blue-100' : 'border-gray-200 hover:border-blue-200 hover:bg-gray-50'">
                <input type="radio" name="payment_method" value="gcash" class="sr-only" x-model="selectedMethod">
                <div class="w-5 h-5 rounded-full border flex items-center justify-center"
                     :class="selectedMethod === 'gcash' ? 'border-blue-500' : 'border-gray-300'">
                    <div class="w-2.5 h-2.5 rounded-full bg-blue-500 transition-transform"
                         :class="selectedMethod === 'gcash' ? 'scale-100' : 'scale-0'"></div>
                </div>
                <div class="ml-4 flex items-center gap-4 flex-1">
                    <div class="w-11 h-11 rounded-lg border border-blue-200 bg-white flex items-center justify-center overflow-hidden">
                        <svg viewBox="0 0 44 44" class="w-9 h-9" aria-hidden="true">
                            <defs>
                                <linearGradient id="gcash-grad" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#0ea5e9"/>
                                    <stop offset="100%" stop-color="#2563eb"/>
                                </linearGradient>
                            </defs>
                            <rect x="3" y="3" width="38" height="38" rx="10" fill="url(#gcash-grad)"/>
                            <path d="M14 18h16v8H14z" fill="#fff" opacity="0.95"/>
                            <circle cx="22" cy="22" r="5" fill="#0ea5e9"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">GCash</p>
                        <p class="text-xs text-gray-500">Pay using your GCash wallet</p>
                    </div>
                </div>
            </label>

            <label class="flex items-center p-4 rounded-xl cursor-pointer border transition-all duration-200"
                   :class="selectedMethod === 'paymaya' ? 'border-emerald-300 bg-emerald-50/80 ring-1 ring-emerald-100' : 'border-gray-200 hover:border-emerald-200 hover:bg-gray-50'">
                <input type="radio" name="payment_method" value="paymaya" class="sr-only" x-model="selectedMethod">
                <div class="w-5 h-5 rounded-full border flex items-center justify-center"
                     :class="selectedMethod === 'paymaya' ? 'border-emerald-500' : 'border-gray-300'">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 transition-transform"
                         :class="selectedMethod === 'paymaya' ? 'scale-100' : 'scale-0'"></div>
                </div>
                <div class="ml-4 flex items-center gap-4 flex-1">
                    <div class="w-11 h-11 rounded-lg border border-emerald-200 bg-white flex items-center justify-center overflow-hidden">
                        <svg viewBox="0 0 44 44" class="w-9 h-9" aria-hidden="true">
                            <defs>
                                <linearGradient id="maya-grad" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#10b981"/>
                                    <stop offset="100%" stop-color="#059669"/>
                                </linearGradient>
                            </defs>
                            <rect x="3" y="3" width="38" height="38" rx="10" fill="url(#maya-grad)"/>
                            <path d="M12 28V16h4l6 8 6-8h4v12h-4v-6l-6 7-6-7v6z" fill="#ecfeff"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">PayMaya / Maya</p>
                        <p class="text-xs text-gray-500">Pay using your Maya wallet</p>
                    </div>
                </div>
            </label>

            <label class="flex items-center p-4 rounded-xl cursor-pointer border transition-all duration-200"
                   :class="selectedMethod === 'card' ? 'border-purple-300 bg-purple-50/80 ring-1 ring-purple-100' : 'border-gray-200 hover:border-purple-200 hover:bg-gray-50'">
                <input type="radio" name="payment_method" value="card" class="sr-only" x-model="selectedMethod">
                <div class="w-5 h-5 rounded-full border flex items-center justify-center"
                     :class="selectedMethod === 'card' ? 'border-purple-500' : 'border-gray-300'">
                    <div class="w-2.5 h-2.5 rounded-full bg-purple-500 transition-transform"
                         :class="selectedMethod === 'card' ? 'scale-100' : 'scale-0'"></div>
                </div>
                <div class="ml-4 flex items-center gap-4 flex-1">
                    <div class="w-11 h-11 rounded-lg border border-purple-200 bg-white flex items-center justify-center overflow-hidden">
                        <svg viewBox="0 0 44 44" class="w-9 h-9" aria-hidden="true">
                            <defs>
                                <linearGradient id="card-grad" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#a855f7"/>
                                    <stop offset="100%" stop-color="#7c3aed"/>
                                </linearGradient>
                            </defs>
                            <rect x="4" y="9" width="36" height="26" rx="6" fill="url(#card-grad)"/>
                            <rect x="7" y="15" width="30" height="4" fill="#f5f3ff" opacity="0.9"/>
                            <rect x="9" y="24" width="11" height="4" rx="2" fill="#e9d5ff"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Credit/Debit Card</p>
                        <p class="text-xs text-gray-500">Visa, Mastercard, JCB</p>
                    </div>
                </div>
            </label>
        </div>

        @error('payment_method')
            <p class="mt-3 text-sm text-red-600 flex items-center gap-2">
                <i class="fi fi-rr-info"></i> {{ $message }}
            </p>
        @enderror

        <div class="mt-6 p-4 rounded-xl border border-gray-200 bg-gray-50">
            <div class="flex items-start gap-3">
                <input type="checkbox" name="accept_terms" id="accept_terms" required
                       class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-400 cursor-pointer">
                <label for="accept_terms" class="text-sm text-gray-600 leading-relaxed cursor-pointer">
                    I have read and agree to the
                    <a href="{{ route('terms') }}" target="_blank" class="text-purple-700 hover:text-purple-800 underline underline-offset-2">Terms & Conditions</a>
                    and
                    <a href="{{ route('privacy') }}" target="_blank" class="text-purple-700 hover:text-purple-800 underline underline-offset-2">Privacy Policy</a>.
                    Refunds are only available within <strong class="text-gray-800">{{ config('billing.subscription.refund_window_days', 3) }} days</strong> of payment.
                </label>
            </div>
            @error('accept_terms')
                <p class="mt-3 text-sm text-red-600 flex items-center gap-2">
                    <i class="fi fi-rr-info"></i> {{ $message }}
                </p>
            @enderror
        </div>

        <button type="submit" id="submit-btn"
                class="mt-6 w-full py-3.5 px-6 rounded-xl font-bold text-white transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed text-base bg-brand-500 hover:bg-brand-600 hover:shadow-lg hover:shadow-purple-300/40 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
            <span id="btn-text">Proceed to Payment</span>
            <i class="fi fi-rr-arrow-right mt-1"></i>
        </button>
    </form>

    <div class="flex items-center justify-center gap-2 text-gray-500 text-sm">
        <i class="fi fi-rr-shield-check"></i>
        <span>Secured by PayMongo | Your payment information is encrypted</span>
    </div>

    <div class="text-center">
        <a href="{{ route('subscription.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:text-purple-900 transition-colors">
            <i class="fi fi-rr-arrow-small-left"></i> Back to Plans
        </a>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('payment-form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fi fi-rr-spinner animate-spin"></i> Processing...';
    });
</script>
@endpush
@endsection

