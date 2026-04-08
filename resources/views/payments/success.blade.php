@extends('layouts.learner-app')

@section('title', 'Payment Successful')

@section('content')
@php
    $scope = $scope ?? 'subscription';
    $isModule = $scope === 'module_purchase';
    $receiptUrl = $receiptUrl ?? route('payment.history');
@endphp
<div class="max-w-3xl mx-auto">
    <div class="rounded-3xl overflow-hidden border border-emerald-100 bg-white shadow-lg">
        <div class="px-8 py-7 text-white" style="background: linear-gradient(130deg, #0F766E 0%, #059669 45%, #10B981 100%);">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-100">Payment Confirmed</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Payment Successful</h1>
                </div>
            </div>
        </div>

        <div class="p-8">
            <p class="text-base text-gray-700">
                @if($isModule)
                    Module access has been granted.
                @else
                    Your payment has been received. Access will update as soon as confirmation is completed.
                @endif
            </p>

            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-start gap-3">
                <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Secure Transaction Completed</p>
                    <p class="text-xs text-emerald-700">Payments are securely processed via PayMongo and recorded in your payment history.</p>
                </div>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('learner.modules.index') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl text-base font-bold text-white bg-brand-500 hover:bg-brand-600 transition-colors">
                    Go to My Learning
                </a>
                <a href="{{ $isModule && $module ? route('learner.modules.show', $module) : route('subscription.index') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl border border-gray-300 text-base font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    {{ $isModule ? 'View Module' : 'View Subscription' }}
                </a>
                <a href="{{ $receiptUrl }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl border border-emerald-300 text-base font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                    View Receipt
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
