@extends('layouts.learner-app')

@php
    $scope = $scope ?? 'subscription';
    $isModule = $scope === 'module_purchase';
    $pageTitle = $isModule ? 'Confirm Module Purchase' : 'Confirm Subscription Purchase';
    $subtitle = $isModule
        ? 'Review module details before redirecting to secure PayMongo checkout.'
        : 'Review subscription details before redirecting to secure PayMongo checkout.';
    $submitUrl = $submitUrl ?? '#';
    $backUrl = $backUrl ?? route('subscription.index');
    $amount = (float) ($amount ?? 0);
@endphp

@section('title', $pageTitle)

@section('content')
<div class="max-w-3xl mx-auto space-y-6 py-6">
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('info'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            {{ session('info') }}
        </div>
    @endif

    <div class="rounded-2xl border border-purple-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-5 text-white" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 45%, #3B0CB1 100%);">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-100">Checkout Summary</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">{{ $pageTitle }}</h1>
            <p class="mt-1 text-sm text-purple-100">{{ $subtitle }}</p>
        </div>

        <div class="px-6 py-4 bg-white space-y-4">
            <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
                <p class="text-xs text-gray-500">Purchase Type</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $isModule ? 'Module Purchase' : 'Subscription' }}</p>
            </div>

            @if($isModule)
                @include('payments.partials.checkout-item-module', ['module' => $module])
            @else
                @include('payments.partials.checkout-item-subscription', ['subscription' => $subscription])
            @endif

            <div class="rounded-xl border border-purple-100 bg-purple-50 px-4 py-3">
                <p class="text-sm font-semibold text-purple-700">Payment Summary</p>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-sm text-purple-700">Price</span>
                    <span class="text-sm font-semibold text-purple-800">PHP {{ number_format($amount, 2) }}</span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-sm font-semibold text-purple-700">Total Amount</span>
                    <span class="text-2xl font-extrabold tracking-tight text-purple-800">PHP {{ number_format($amount, 2) }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-start gap-3">
                <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Secure Checkout</p>
                    <p class="text-xs text-emerald-700">Payments are securely processed by PayMongo.</p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ $submitUrl }}" method="POST" id="payment-form" class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
        @csrf

        <h2 class="text-lg font-bold text-gray-900">Confirm And Continue</h2>
        <p class="mt-1 text-sm text-gray-600">You will choose your preferred payment method directly on PayMongo checkout.</p>

        <div class="mt-6 p-4 rounded-xl border border-gray-200 bg-gray-50">
            <div class="flex items-start gap-3">
                <input type="checkbox" name="accept_terms" id="accept_terms" required class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-400 cursor-pointer">
                <label for="accept_terms" class="text-sm text-gray-600 leading-relaxed cursor-pointer">
                    I have read and agree to the
                    <a href="{{ route('terms') }}" target="_blank" class="text-purple-700 hover:text-purple-800 underline underline-offset-2">Terms & Conditions</a>
                    and
                    <a href="{{ route('privacy') }}" target="_blank" class="text-purple-700 hover:text-purple-800 underline underline-offset-2">Privacy Policy</a>.
                </label>
            </div>
            @error('accept_terms')
                <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" id="submit-btn" class="mt-6 w-full py-3.5 px-6 rounded-xl font-bold text-white transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed text-base bg-brand-500 hover:bg-brand-600 hover:shadow-lg hover:shadow-purple-300/40 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
            <span id="btn-text">Proceed to PayMongo</span>
        </button>
    </form>

    <div class="text-center">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:text-purple-900 transition-colors">
            Back
        </a>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('payment-form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = 'Processing...';
    });
</script>
@endpush
@endsection
