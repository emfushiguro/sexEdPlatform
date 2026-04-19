@extends('layouts.instructor-app')

@section('title', 'Instructor Payment Receipt')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    @php
        $details = (array) ($payment->payment_details ?? []);
        $reference = $payment->transaction_id ?: (string) $payment->id;
        $datePaid = $payment->paid_at ?? $payment->created_at;
        $method = strtoupper((string) ($payment->method ?? $details['payment_method'] ?? 'N/A'));
        $planName = $payment->subscription?->plan?->name
            ?? ($details['plan_name'] ?? $payment->subscription?->getPlanLabel() ?? 'Instructor Plan');
        $validUntil = $payment->subscription?->end_date?->format('M d, Y') ?? 'N/A';
    @endphp

    <div id="printable-receipt" class="bg-white rounded-2xl border border-gray-200 shadow-xl text-gray-900">
        <div class="px-8 pt-9 pb-7 border-b border-gray-200">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-bold leading-tight mt-5">Instructor Payment Receipt</h2>
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
                    <span class="font-medium">Instructor</span>
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
                    <span class="font-medium">Plan</span>
                    <span class="text-right">{{ $planName }}</span>
                </div>
                <div class="flex items-start justify-between gap-8 py-3">
                    <span class="font-medium">Valid Until</span>
                    <span class="text-right">{{ $validUntil }}</span>
                </div>
            </div>

            <div class="my-8 border-t border-gray-200"></div>

            <div class="flex items-start justify-between gap-8 py-2">
                <span class="text-base font-semibold">Amount Paid</span>
                <span class="text-2xl font-bold text-right">PHP {{ number_format((float) $payment->amount, 2) }}</span>
            </div>

            <p class="text-xs mt-10 pt-5 border-t border-gray-200 text-center">This receipt confirms successful instructor subscription payment processing.</p>
        </div>
    </div>

    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center print:hidden">
        <button onclick="window.print()" class="py-3 px-6 rounded-xl font-bold bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all">
            Print Receipt
        </button>

        <a href="{{ route('instructor.payments.history') }}" class="py-3 px-6 rounded-xl font-bold bg-brand-500 hover:bg-brand-600 text-white transition-all">
            Back to Payment History
        </a>
    </div>
</div>
@endsection
