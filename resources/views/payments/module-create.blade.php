@extends('layouts.learner-app')

@section('title', 'Complete Module Payment')

@section('content')
@php
    $paymongoMode = $paymongoMode ?? 'unknown';
    $isSandboxMode = $paymongoMode === 'sandbox';
    $billingNameDefault = old('billing_name', auth()->user()->name ?? '');
    $billingEmailDefault = old('billing_email', auth()->user()->email ?? '');
    $billingPhoneDefault = old('billing_phone', '');
@endphp
<div class="max-w-3xl mx-auto space-y-6 py-6">
    @if($isSandboxMode)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Sandbox mode is active. This checkout uses PayMongo test credentials and does not process real charges.
        </div>
    @elseif($paymongoMode === 'live')
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Live mode detected. Payments will be processed as real transactions.
        </div>
    @endif

    <div class="rounded-2xl border border-purple-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-5 text-white" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 45%, #3B0CB1 100%);">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-100">Secure Checkout</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Complete Module Payment</h1>
            <p class="mt-1 text-sm text-purple-100">Choose a payment method to unlock this paid module.</p>
        </div>
        <div class="px-6 py-4 bg-white">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Module</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $module->title }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Access Type</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">One-time purchase</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
                    <p class="text-xs text-gray-500">Enrollment Mode</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst($module->enrollment_mode) }}</p>
                </div>
            </div>
            <div class="mt-4 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-purple-700">Total Amount</span>
                <span class="text-2xl font-extrabold tracking-tight text-purple-800">PHP {{ number_format($amount, 2) }}</span>
            </div>
        </div>
    </div>

    <form action="{{ route('learner.modules.purchase.process', $module) }}" method="POST" id="payment-form" x-data="{ selectedMethod: '{{ old('payment_method', 'card') }}' }" class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
        @csrf

        <h2 class="text-lg font-bold text-gray-900">Preferred Payment Method</h2>
        <p class="mt-1 text-sm text-gray-500">You will be redirected to a secure PayMongo checkout after submission.</p>
        <p class="mt-1 text-xs text-gray-500">Your selection is used as a preferred method, and PayMongo can still display other enabled payment options.</p>

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
                    <div class="w-11 h-11 rounded-lg border border-blue-200 bg-white flex items-center justify-center text-blue-700 font-bold text-xs">GCash</div>
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
                    <div class="w-11 h-11 rounded-lg border border-emerald-200 bg-white flex items-center justify-center text-emerald-700 font-bold text-xs">Maya</div>
                    <div>
                        <p class="font-semibold text-gray-900">PayMaya / Maya</p>
                        <p class="text-xs text-gray-500">Pay using your Maya wallet</p>
                    </div>
                </div>
            </label>

            <label class="flex items-center p-4 rounded-xl cursor-pointer border transition-all duration-200"
                   :class="selectedMethod === 'grab_pay' ? 'border-lime-300 bg-lime-50/80 ring-1 ring-lime-100' : 'border-gray-200 hover:border-lime-200 hover:bg-gray-50'">
                <input type="radio" name="payment_method" value="grab_pay" class="sr-only" x-model="selectedMethod">
                <div class="w-5 h-5 rounded-full border flex items-center justify-center"
                     :class="selectedMethod === 'grab_pay' ? 'border-lime-500' : 'border-gray-300'">
                    <div class="w-2.5 h-2.5 rounded-full bg-lime-500 transition-transform"
                         :class="selectedMethod === 'grab_pay' ? 'scale-100' : 'scale-0'"></div>
                </div>
                <div class="ml-4 flex items-center gap-4 flex-1">
                    <div class="w-11 h-11 rounded-lg border border-lime-200 bg-white flex items-center justify-center text-lime-700 font-bold text-[10px]">GrabPay</div>
                    <div>
                        <p class="font-semibold text-gray-900">GrabPay</p>
                        <p class="text-xs text-gray-500">Pay using your GrabPay wallet</p>
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
                    <div class="w-11 h-11 rounded-lg border border-purple-200 bg-white flex items-center justify-center text-purple-700 font-bold text-xs">Card</div>
                    <div>
                        <p class="font-semibold text-gray-900">Credit/Debit Card <span class="ml-1 rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-700">No QR</span></p>
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

        <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-sm font-semibold text-gray-900">Billing Information</h3>
            <p class="mt-1 text-xs text-gray-500">These details are attached to your transaction record.</p>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label for="billing_name" class="block text-xs font-semibold text-gray-600">Full Name</label>
                    <input type="text" id="billing_name" name="billing_name" value="{{ $billingNameDefault }}" required
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                    @error('billing_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="billing_email" class="block text-xs font-semibold text-gray-600">Email</label>
                    <input type="email" id="billing_email" name="billing_email" value="{{ $billingEmailDefault }}" required
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                    @error('billing_email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="billing_phone" class="block text-xs font-semibold text-gray-600">Phone (optional)</label>
                    <input type="text" id="billing_phone" name="billing_phone" value="{{ $billingPhoneDefault }}"
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                    @error('billing_phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 rounded-xl border border-gray-200 bg-gray-50">
            <div class="flex items-start gap-3">
                <input type="checkbox" name="accept_terms" id="accept_terms" required
                       class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-400 cursor-pointer">
                <label for="accept_terms" class="text-sm text-gray-600 leading-relaxed cursor-pointer">
                    I have read and agree to the
                    <a href="{{ route('terms') }}" target="_blank" class="text-purple-700 hover:text-purple-800 underline underline-offset-2">Terms & Conditions</a>
                    and
                    <a href="{{ route('privacy') }}" target="_blank" class="text-purple-700 hover:text-purple-800 underline underline-offset-2">Privacy Policy</a>.
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
        <a href="{{ route('learner.modules.show', $module) }}" class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:text-purple-900 transition-colors">
            <i class="fi fi-rr-arrow-small-left"></i> Back to Module Overview
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
