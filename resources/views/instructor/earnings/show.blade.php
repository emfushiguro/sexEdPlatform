@extends('layouts.instructor-app')

@section('title', 'Transaction Details')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transaction Details</h1>
                <p class="mt-1 text-sm text-gray-600">Detailed breakdown of this module sale snapshot.</p>
            </div>
            <a href="{{ route('instructor.earnings.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to Earnings</a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Gross Amount</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">P{{ number_format((float) $ledger->gross_amount, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Commission</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">P{{ number_format((float) $ledger->commission_amount, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Net Earnings</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">P{{ number_format((float) $ledger->instructor_earnings_amount, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Payout Status</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ ucfirst($ledger->payout_status) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Sale Snapshot</h2>
            </div>
            <dl class="grid gap-x-8 gap-y-4 px-6 py-5 md:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Module</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $ledger->module?->title ?? 'Unknown Module' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Learner</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $ledger->learner_name_snapshot ?: ($ledger->learner?->name ?? 'Unknown Learner') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Payment Transaction</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $ledger->payment?->transaction_id ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Occurred At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ optional($ledger->occurred_at)->toDayDateTimeString() ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Commission Percent Snapshot</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ number_format((float) $ledger->commission_percent_snapshot, 2) }}%</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Tax Basis Snapshot</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ strtoupper((string) $ledger->tax_basis_snapshot) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Refund Policy Snapshot</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ strtoupper((string) $ledger->refund_policy_snapshot) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Sale Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ ucfirst((string) $ledger->sale_status) }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
