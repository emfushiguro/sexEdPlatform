@extends('layouts.instructor-app')

@section('title', 'Module Earnings')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h1 class="text-2xl font-bold text-gray-900">Module Earnings</h1>
            <p class="mt-1 text-sm text-gray-600">Track your paid module sales, commission deductions, and net earnings.</p>

            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3">
                <p class="text-sm font-semibold text-indigo-900">Formula: Net earnings = Gross amount - Platform commission.</p>
                <p class="mt-1 text-xs text-indigo-800">Refund policy: module purchase refunds are currently disabled.</p>
                @if(!empty($effectiveCommissionPolicy))
                    <p class="mt-1 text-xs text-indigo-800">
                        Current commission rate for new sales: {{ number_format((float) $effectiveCommissionPolicy['commission_percent'], 2) }}%.
                    </p>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Total Sales</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_sales']) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Gross Revenue</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['gross_revenue'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Platform Commission</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['platform_commission'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-purple-100 bg-purple-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-700">Net Earnings</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['net_earnings'], 2) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Sales Transactions</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Learner</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Gross</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Net Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Payout Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ optional($tx->occurred_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $tx->module?->title ?? 'Unknown Module' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $tx->learner_name_snapshot ?: ($tx->learner?->name ?? 'Unknown Learner') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">P{{ number_format((float) $tx->gross_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">P{{ number_format((float) $tx->commission_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">P{{ number_format((float) $tx->instructor_earnings_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ ucfirst($tx->payout_status) }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('instructor.earnings.show', $tx) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                            View details
                                        </a>
                                        <form method="POST" action="{{ route('instructor.earnings.visibility.destroy', $tx) }}" onsubmit="return confirm('Hide this row from your list?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-semibold text-rose-600 hover:text-rose-800">
                                                Hide row
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">No earnings transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
@endsection
