@extends('layouts.learner-app')

@section('title', 'Payment Receipt')

@section('content')
<div class="max-w-3xl mx-auto py-8">

    @if(session('success'))
        <div x-data="{ show: true }" x-init="
            Toastify({
                text: '{{ session('success') }}',
                duration: 3000,
                gravity: 'top',
                position: 'right',
                className: 'bg-green-500 rounded-xl font-medium'
            }).showToast();
        "></div>
    @endif

    @php
        $details = (array) ($payment->payment_details ?? []);
        $isModulePayment = $payment->isModulePurchase() || isset($details['module_id']);
        $paymongoRef = $details['paymongo_payment_id'] ?? $details['paymongo_link_id'] ?? null;
        $reference = $payment->transaction_id ?: ($paymongoRef ? strtoupper(substr((string) $paymongoRef, -10)) : (string) $payment->id);
        $datePaid = $payment->paid_at ?? $payment->created_at;
        $method = strtoupper((string) ($payment->method ?? $details['payment_method'] ?? 'N/A'));
        $itemName = $isModulePayment
            ? ($payment->modulePurchase?->module?->title ?? ($details['module_title'] ?? 'Module Purchase'))
            : ($payment->subscription?->plan?->name
                ?? ($details['plan_name'] ?? $payment->subscription?->getPlanLabel() ?? 'Subscription Plan'));
        $billingCycle = $isModulePayment ? 'One-time purchase' : ucfirst((string) ($details['billing_cycle'] ?? 'monthly'));
        $validUntil = $isModulePayment ? 'Lifetime access' : ($payment->subscription?->end_date?->format('M d, Y') ?? 'N/A');
    @endphp

    {{-- Main Receipt Card --}}
    <div id="printable-receipt" class="bg-white rounded-2xl border border-gray-200 shadow-xl text-gray-900">
        <div class="px-8 pt-9 pb-7 border-b border-gray-200">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-bold leading-tight mt-5">Payment Receipt</h2>
                    <p class="text-sm mt-1">Conscious Connections</p>
                </div>
                <div class="text-right text-sm leading-6 mt-5">
                    <p><span class="font-medium">Issued:</span> {{ now()->format('M d, Y h:i A') }}</p>
                    <p><span class="font-medium">Status:</span> Paid</p>
                </div>
            </div>
        </div>

        <div class="px-8 py-8">
            <div class="text-sm divide-y divide-gray-100">
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">Reference Number</span>
                    <span class="font-mono text-right">{{ $reference }}</span>
                </div>
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">Date Paid</span>
                    <span class="text-right">{{ $datePaid?->format('M d, Y h:i A') ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">Learner Name</span>
                    <span class="text-right">{{ $payment->user?->name ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">Payment Method</span>
                    <span class="text-right">{{ $method }}</span>
                </div>
            </div>

            <div class="my-8 border-t border-gray-200"></div>

            <div class="text-sm divide-y divide-gray-100">
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">{{ $isModulePayment ? 'Module' : 'Plan Level' }}</span>
                    <span class="text-right">{{ $itemName }}</span>
                </div>
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">{{ $isModulePayment ? 'Purchase Type' : 'Billing Cycle' }}</span>
                    <span class="text-right">{{ $billingCycle }}</span>
                </div>
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">{{ $isModulePayment ? 'Access Duration' : 'Valid Until' }}</span>
                    <span class="text-right">{{ $validUntil }}</span>
                </div>
            </div>

            <div class="my-8 border-t border-gray-200"></div>

            <div class="flex items-start justify-between gap-8 py-2">
                <span class="text-base font-semibold">Amount Paid</span>
                <span class="text-2xl font-bold text-right">PHP {{ number_format((float) $payment->amount, 2) }}</span>
            </div>

            <p class="text-xs mt-10 pt-5 border-t border-gray-200 text-center">This receipt confirms successful payment processing. Keep a copy for your records.</p>
        </div>
    </div>

    {{-- Actions (Hidden on Print) --}}
    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center print:hidden">
        <button onclick="window.print()" class="py-3 px-6 rounded-xl font-bold bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all flex items-center justify-center gap-2">
            <i class="fi fi-rr-print"></i> Print Receipt
        </button>

        <a href="{{ $isModulePayment ? route('payment.history') : route('subscription.index') }}" class="py-3 px-6 rounded-xl font-bold bg-brand-500 hover:bg-brand-600 text-white transition-all hover:shadow-lg hover:shadow-brand-500/25 flex items-center justify-center gap-2">
            <i class="fi fi-rr-rocket-lunch"></i> {{ $isModulePayment ? 'Back to Payment History' : 'Back to Subscriptions' }}
        </a>
    </div>
</div>

{{-- Add print-specific styles --}}
<style>
    @media print {
        @page {
            size: auto;
            margin: 10mm;
        }

        body { visibility: hidden; }
        #printable-receipt {
            visibility: visible;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
            border: 1px solid #e5e7eb;
            border-radius: 0;
            margin: 0;
        }

        /* Overrides to ensure background colors print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>
@endsection
