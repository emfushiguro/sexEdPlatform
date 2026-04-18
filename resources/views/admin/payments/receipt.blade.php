@extends('layouts.admin')

@section('title', 'Admin Payment Receipt')
@section('page-title', 'Payment Receipt')

@php
    $status = is_object($payment->status) ? $payment->status->value : (string) $payment->status;
    $statusClasses = [
        'completed' => 'bg-emerald-100 text-emerald-700',
        'failed' => 'bg-rose-100 text-rose-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-brand-100 text-brand-700',
        'refunded' => 'bg-gray-100 text-gray-600',
    ];

    $itemName = $isModulePurchase
        ? ($module?->title ?? data_get($details, 'module_title', 'Module Purchase'))
        : ($subscription?->plan?->name
            ?? data_get($details, 'plan_name')
            ?? $subscription?->getPlanLabel()
            ?? 'Subscription Plan');

    $billingCycle = $isModulePurchase
        ? 'One-time purchase'
        : ucfirst((string) (data_get($details, 'billing_cycle') ?: 'monthly'));

    $validUntil = $isModulePurchase
        ? 'Lifetime access'
        : ($subscription?->end_date?->format('M d, Y') ?? 'N/A');

    $recipientLabel = $isModulePurchase ? 'Learner' : 'Subscriber';
@endphp

@section('content')
<div class="space-y-6">
    <div class="print:hidden flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.payments.show', $payment) }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            <span>Back to Payment Details</span>
        </a>

        <button type="button"
                onclick="window.print()"
                class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">
            Print Receipt
        </button>
    </div>

    <section id="admin-payment-receipt" class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Payment Management</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Admin Payment Receipt</h2>
                    <p class="mt-1 text-sm text-gray-500">Audit-friendly payment confirmation view for admin records.</p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </span>
            </div>
        </div>

        <div class="grid gap-6 p-6 lg:grid-cols-2">
            <div class="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Receipt Summary</p>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">Reference</span>
                        <span class="font-mono font-semibold text-gray-900">{{ $reference }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">Issued</span>
                        <span class="font-semibold text-gray-900">{{ now()->format('M d, Y h:i A') }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">Paid At</span>
                        <span class="font-semibold text-gray-900">{{ $datePaid?->format('M d, Y h:i A') ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">Payment Method</span>
                        <span class="font-semibold text-gray-900">{{ $method }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">Amount Paid</span>
                        <span class="text-lg font-bold text-emerald-600">PHP {{ number_format((float) $payment->amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Purchase Details</p>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">Type</span>
                        <span class="font-semibold text-gray-900">{{ $isModulePurchase ? 'Module Purchase' : 'Subscription Payment' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">{{ $isModulePurchase ? 'Module' : 'Plan' }}</span>
                        <span class="font-semibold text-right text-gray-900">{{ $itemName }}</span>
                    </div>
                    @if($isModulePurchase)
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-500">Instructor</span>
                            <span class="font-semibold text-right text-gray-900">{{ $moduleInstructor?->name ?? 'N/A' }}</span>
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-500">{{ $isModulePurchase ? 'Access Duration' : 'Billing Cycle' }}</span>
                        <span class="font-semibold text-gray-900">{{ $isModulePurchase ? $validUntil : $billingCycle }}</span>
                    </div>
                    @unless($isModulePurchase)
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-500">Valid Until</span>
                            <span class="font-semibold text-gray-900">{{ $validUntil }}</span>
                        </div>
                    @endunless
                </div>
            </div>

            <div class="rounded-[24px] border border-gray-200 bg-white p-5 lg:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Account Details</p>
                <div class="mt-4 grid gap-4 text-sm md:grid-cols-3">
                    <div>
                        <p class="text-gray-500">{{ $recipientLabel }}</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $payment->user?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Email</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $payment->user?->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Transaction ID</p>
                        <p class="mt-1 font-mono font-semibold text-gray-900">{{ $payment->transaction_id ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 px-6 py-4">
            <p class="text-center text-xs text-gray-500">This receipt confirms payment state at the time of viewing. Keep this record for compliance and audit tracking.</p>
        </div>
    </section>
</div>
@endsection

@section('styles')
<style>
    @media print {
        aside,
        header,
        .print\:hidden {
            display: none !important;
        }

        main {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #admin-payment-receipt {
            box-shadow: none !important;
            border-radius: 0 !important;
            border: 1px solid #e5e7eb !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>
@endsection
