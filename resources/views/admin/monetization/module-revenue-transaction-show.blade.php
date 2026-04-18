@extends('layouts.admin')

@section('title', 'Transaction Details')
@section('page-title', 'Module Revenue Transaction Details')

@section('content')
    <div class="space-y-6">
        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Transaction</p>
                        <h1 class="mt-2 text-2xl font-bold text-gray-900">#{{ $transaction->id }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Reference: {{ $transaction->payment?->transaction_id ?? 'N/A' }}</p>
                    </div>
                    <a href="{{ route('admin.monetization.module-revenue.index') }}"
                       class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">
                        Back to Revenue Table
                    </a>
                </div>
            </div>

            <div class="grid gap-4 px-6 py-6 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Gross Amount</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">PHP {{ number_format((float) $transaction->gross_amount, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Platform Commission</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">PHP {{ number_format((float) $transaction->commission_amount, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Instructor Earnings</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">PHP {{ number_format((float) $transaction->instructor_earnings_amount, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Payout Status</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ ucfirst((string) $transaction->payout_status) }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Transaction Metadata</h2>
            </div>
            <dl class="grid gap-4 px-6 py-6 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Module</dt>
                    <dd class="mt-2 text-sm font-semibold text-gray-900">{{ $transaction->module?->title ?? 'Unknown module' }}</dd>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Occurred At</dt>
                    <dd class="mt-2 text-sm font-semibold text-gray-900">{{ optional($transaction->occurred_at)->format('M d, Y h:i A') ?? 'N/A' }}</dd>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Learner</dt>
                    <dd class="mt-2 text-sm font-semibold text-gray-900">{{ $transaction->learner?->name ?? $transaction->learner_name_snapshot ?? 'Unknown learner' }}</dd>
                    <p class="text-xs text-gray-500">{{ $transaction->learner?->email ?? 'No email' }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</dt>
                    <dd class="mt-2 text-sm font-semibold text-gray-900">{{ $transaction->instructor?->name ?? 'Unknown instructor' }}</dd>
                    <p class="text-xs text-gray-500">{{ $transaction->instructor?->email ?? 'No email' }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Method</dt>
                    <dd class="mt-2 text-sm font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', (string) ($transaction->payment?->method ?? 'unknown'))) }}</dd>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Status</dt>
                    <dd class="mt-2 text-sm font-semibold text-gray-900">{{ ucfirst((string) ($transaction->payment?->status?->value ?? $transaction->payment?->status ?? 'N/A')) }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
