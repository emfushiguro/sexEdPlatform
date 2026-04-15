@extends('layouts.admin')

@section('title', 'Instructor Revenue Details')
@section('page-title', 'Instructor Revenue Details')

@section('content')
    <div class="space-y-6">
        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Instructor Roll-up</p>
                        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $instructor->name }}</h1>
                        <p class="mt-1 text-sm text-gray-500">{{ $instructor->email }}</p>
                    </div>
                    <a href="{{ route('admin.monetization.module-revenue.index') }}"
                       class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">
                        Back to Revenue Table
                    </a>
                </div>
            </div>

            <div class="grid gap-4 px-6 py-6 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Transactions</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_transactions']) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Module Revenue</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">PHP {{ number_format((float) $stats['total_module_revenue'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Platform Fee</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">PHP {{ number_format((float) $stats['total_platform_commission'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Instructor Earnings</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">PHP {{ number_format((float) $stats['total_instructor_earnings'], 2) }}</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Module</span>
                        <select name="module_id" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All modules</option>
                            @foreach($modules as $module)
                                <option value="{{ $module->id }}" @selected((string) request('module_id') === (string) $module->id)>{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date From</span>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date To</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                    </label>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex h-[46px] items-center justify-center rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Apply</button>
                        <a href="{{ route('admin.monetization.module-revenue.instructors.show', $instructor) }}" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">Reset</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Module</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Learner</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Revenue</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Commission</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Earnings</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($transactions as $transaction)
                            <tr class="transition hover:bg-brand-50/55">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ ($transactions->firstItem() ?? 1) + $loop->index }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $transaction->module?->title ?? 'Unknown module' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $transaction->learner?->name ?? $transaction->learner_name_snapshot ?? 'Unknown learner' }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">PHP {{ number_format((float) $transaction->gross_amount, 2) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">PHP {{ number_format((float) $transaction->commission_amount, 2) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">PHP {{ number_format((float) $transaction->instructor_earnings_amount, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ optional($transaction->occurred_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.monetization.module-revenue.transactions.show', $transaction) }}"
                                       class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-white transition hover:bg-brand-50"
                                       title="View transaction details">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-14 text-center">
                                    <div class="mx-auto max-w-sm">
                                        <h3 class="text-sm font-semibold text-gray-900">No instructor transactions found</h3>
                                        <p class="mt-1 text-sm text-gray-500">Try broadening the selected date or module filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                {{ $transactions->links() }}
            </div>
        </section>
    </div>
@endsection
